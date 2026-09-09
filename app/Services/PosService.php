<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Inventory;
use App\Models\PosOrder;
use App\Models\PosOrderItem;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PosService
{
    /**
     * Create a POS order with items.
     *
     * @param array $data [guest_id, room_id, booking_id (optional), notes (optional)]
     * @param array $items [[inventory_id or item_name, quantity, price_per_unit]]
     * @return PosOrder
     * @throws \Exception
     */
    public function createOrder(array $data, array $items): PosOrder
    {
        return DB::transaction(function () use ($data, $items) {
            if (empty($items)) {
                throw new \Exception('Order must contain at least one item.');
            }

            $subtotal = 0;

            // Calculate subtotal first
            foreach ($items as $item) {
                $quantity = $item['quantity'] ?? 1;
                $price = $item['price_per_unit'] ?? 0;
                $subtotal += $quantity * $price;
            }

            // Tax - FORCED TO 0 (No tax as per user request)
            $taxAmount = 0;
            $totalAmount = $subtotal + $taxAmount;
            $grandTotal = $totalAmount - ($data['discount_amount'] ?? 0);

            // Guard against placing a cash order when the cashier's own "uang
            // diterima" is less than the order total -- the create form has a
            // client-side calculator for this but never enforced it, and never
            // sent the value to the server either, so the check was skippable
            // entirely (both by mistake and by tampering).
            if (($data['payment_method'] ?? null) === 'cash' && isset($data['customer_paid'])) {
                if ((float) $data['customer_paid'] < $grandTotal) {
                    throw new \Exception('Uang diterima (Rp ' . number_format($data['customer_paid'], 0, ',', '.') . ') kurang dari total order (Rp ' . number_format($grandTotal, 0, ',', '.') . ').');
                }
            }

            // Create the order (order_number auto-generated via model boot).
            // Retry a few times on a duplicate order_number: nextOrderNumber()
            // is locked but not airtight across every isolation level, so this
            // is a cheap safety net against two concurrent creates landing on
            // the same sequence.
            $orderAttrs = [
                'order_number' => null, // Auto-generated in model boot
                'guest_id' => $data['guest_id'] ?? null,
                'room_id' => $data['room_id'] ?? null,
                'booking_id' => $data['booking_id'] ?? null,
                'user_id' => Auth::id(),
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'total_amount' => $totalAmount - ($data['discount_amount'] ?? 0),
                'payment_method' => 'cash',
                'payment_status' => 'unpaid',
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
            ];
            $order = null;
            for ($attempt = 1; $attempt <= 5; $attempt++) {
                try {
                    $order = PosOrder::create($orderAttrs);
                    break;
                } catch (\Illuminate\Database\QueryException $e) {
                    $isDuplicateOrderNumber = ($e->errorInfo[1] ?? null) == 1062
                        && str_contains($e->getMessage(), 'order_number');
                    if (!$isDuplicateOrderNumber || $attempt === 5) {
                        throw $e;
                    }
                }
            }

            // Create order items
            foreach ($items as $item) {
                $quantity = $item['quantity'] ?? 1;
                $price = $item['price_per_unit'] ?? 0;
                $itemSubtotal = $quantity * $price;

                // Get cost price from inventory if linked
                $costPrice = 0;
                if (isset($item['inventory_id'])) {
                    $inventory = Inventory::find($item['inventory_id']);
                    if ($inventory) {
                        $costPrice = $inventory->purchase_price ?? 0;
                    }
                }

                PosOrderItem::create([
                    'pos_order_id' => $order->id,
                    'inventory_id' => $item['inventory_id'] ?? null,
                    'item_name' => $item['item_name'] ?? 'Unknown Item',
                    'quantity' => $quantity,
                    'price_per_unit' => $price,
                    'cost_price' => $costPrice,
                    'subtotal' => $itemSubtotal,
                ]);

                // Decrement inventory if linked
                if (isset($item['inventory_id'])) {
                    $inventory = Inventory::find($item['inventory_id']);
                    if ($inventory) {
                        $this->updateInventory($inventory, $quantity);
                    }
                }
            }

            // Handle payment if bank_account_id is provided and not marked as unpaid
            if (!empty($data['bank_account_id'])) {
                $method = $data['payment_method'] ?? 'cash';
                $accountId = $data['bank_account_id'];
                
                // Determine method from account name if not explicitly set to something else
                if (!$data['payment_method'] || $data['payment_method'] === 'cash') {
                    $account = \App\Models\BankAccount::find($accountId);
                    if ($account) {
                        $method = str_contains(strtolower($account->name), 'tunai') ? 'cash' : 'bank_transfer';
                    }
                }

                if ($method !== 'charge_to_room' && $method !== 'unpaid') {
                    $this->processPayment($order, $method, $accountId);
                }
            } elseif (($data['payment_method'] ?? null) === 'charge_to_room') {
                // "Charge to Room" is a sentinel option normalized out of
                // bank_account_id before it reaches here (see PosController),
                // so it never hit the branch above. Without this, the order
                // stayed status=pending and was excluded from the booking's
                // Grand Total/Remaining Balance (BookingPriceService only
                // counts status=completed), even though it already showed up
                // in the Extra Charges list since that only checks booking_id.
                $this->processPayment($order, 'charge_to_room');
            } elseif (($data['payment_method'] ?? null) === 'cash') {
                // Backward compatibility for simple cash without account
                $this->processPayment($order, 'cash');
            }

            \App\Models\AuditLog::log(
                'pos.order_created',
                "POS order #{$order->order_number} created with total {$order->total_amount}",
                $order
            );

            return $order->load('items');
        });
    }

    /**
     * Process payment for a POS order.
     *
     * @param PosOrder $order
     * @param string $paymentMethod cash, credit_card, charge_to_room, qris, bank_transfer
     * @param int|null $bankAccountId
     * @param float|null $amount
     * @return PosOrder
     * @throws \Exception
     */
    public function processPayment(PosOrder $order, string $paymentMethod, ?int $bankAccountId = null, ?float $amount = null): PosOrder
    {
        return DB::transaction(function () use ($order, $paymentMethod, $bankAccountId, $amount) {
            if ($order->payment_status === 'paid') {
                throw new \Exception('Order is already paid.');
            }

            $paymentAmount = $amount ?? $order->total_amount;

            if ($paymentMethod === 'charge_to_room') {
                if (!$order->booking_id) {
                    throw new \Exception('Cannot charge to room: no booking linked to this order.');
                }
                $booking = Booking::findOrFail($order->booking_id);

                // Charge to room = add to the room bill, NOT a payment. Leaves the
                // order completed+unpaid so it's counted in the booking's remaining
                // balance (settled when the room bill is paid at checkout). Do NOT
                // create a payment transaction or mark it paid here.
                return $this->chargeToRoomBooking($order, $booking);
            }

            // Update order status
            $order->update([
                'payment_method' => $paymentMethod,
                'payment_status' => 'paid',
                'status' => 'completed',
            ]);

            // Create a transaction record for the POS payment
            Transaction::create([
                'hotel_id' => active_hotel_id(),
                'booking_id' => $order->booking_id,
                'guest_id' => $order->guest_id,
                'user_id' => Auth::id(),
                'bank_account_id' => $bankAccountId,
                'type' => 'payment',
                'amount' => $paymentAmount,
                'payment_method' => $paymentMethod,
                'reference_id' => $order->order_number,
                'description' => "POS payment for order {$order->order_number} via {$paymentMethod}",
                'status' => 'success',
                'is_realized' => true,
            ]);

            // Update account balance
            if ($bankAccountId) {
                $account = \App\Models\BankAccount::find($bankAccountId);
                if ($account) {
                    $account->increment('balance', $paymentAmount);
                    $account->increment('available_balance', $paymentAmount);
                }
            }

            \App\Models\AuditLog::log(
                'pos.payment_processed',
                "Payment processed for POS order {$order->order_number}: {$paymentMethod}",
                $order
            );

            return $order->refresh();
        });
    }

    /**
     * Link a POS order charge to a room booking.
     *
     * @param PosOrder $order
     * @param Booking $booking
     * @return Transaction
     * @throws \Exception
     */
    public function chargeToRoomBooking(PosOrder $order, Booking $booking): PosOrder
    {
        return DB::transaction(function () use ($order, $booking) {
            // Ensure the booking is active
            if ($booking->status !== 'checked_in') {
                throw new \Exception('Can only charge to an active (checked_in) booking.');
            }

            // Mark the order completed + unpaid and charge it to the room. The
            // booking's remaining balance counts POS orders that are completed AND
            // unpaid (BookingPriceService::grandTotal / booking show / checkout),
            // so completing it here is what makes the charge appear on the room
            // bill. Leaving it 'pending' (the old behavior) meant the charge was
            // linked but never added to the total. It is settled when the room
            // bill is paid at checkout (BookingService::checkOut marks it paid).
            $order->update([
                'booking_id' => $booking->id,
                'room_id' => $order->room_id ?: $booking->room_id,
                'guest_id' => $order->guest_id ?: $booking->guest_id,
                'payment_method' => 'charge_to_room',
                'payment_status' => 'unpaid',
                'status' => 'completed',
            ]);

            \App\Models\AuditLog::log(
                'pos.charge_to_room',
                "POS order {$order->order_number} ({$order->total_amount}) charged to booking #{$booking->id}",
                $order
            );

            return $order->refresh();
        });
    }

    /**
     * Delete an item from a POS order and recalculate totals.
     */
    public function deleteItemFromOrder(PosOrderItem $item)
    {
        return DB::transaction(function () use ($item) {
            $order = $item->posOrder;

            if ($order->payment_status === 'paid' || $order->status === 'cancelled') {
                throw new \Exception('Cannot delete item from a paid or cancelled order.');
            }

            // Restore inventory if applicable
            if ($item->inventory_id) {
                $inventory = Inventory::find($item->inventory_id);
                if ($inventory) {
                    $inventory->increment('stock', $item->quantity);
                }
            }

            $item->delete();

            // Recalculate order totals
            $newSubtotal = $order->items()->sum('subtotal');
            $newTax = 0; // Tax - FORCED TO 0
            $newTotal = $newSubtotal + $newTax - $order->discount_amount;

            if ($order->items()->count() === 0) {
                $order->delete();
            } else {
                $order->update([
                    'subtotal' => $newSubtotal,
                    'tax_amount' => $newTax,
                    'total_amount' => $newTotal,
                ]);
            }
            
            return true;
        });
    }

    /**
     * Decrement inventory stock.
     *
     * @param Inventory $item
     * @param int $quantity
     * @return Inventory
     * @throws \Exception
     */
    public function updateInventory(Inventory $item, int $quantity): Inventory
    {
        if ($item->stock < $quantity) {
            throw new \Exception("Insufficient stock for {$item->name}. Available: {$item->stock}, Requested: {$quantity}");
        }

        $item->decrement('stock', $quantity);

        $remainingStock = $item->stock - $quantity;
        \App\Models\AuditLog::log(
            'inventory.decremented',
            "Inventory decremented: {$item->name} ({$quantity} {$item->unit}). Remaining: {$remainingStock}",
            $item
        );

        return $item->refresh();
    }

    /**
     * Get daily sales report.
     *
     * @param string|\Carbon\Carbon $date
     * @return array
     */
    public function getDailySalesReport($date): array
    {
        $date = is_string($date) ? \Carbon\Carbon::parse($date) : $date;

        $orders = PosOrder::whereDate('created_at', $date->toDateString())
            ->where('status', 'completed')
            ->with(['guest', 'booking.room'])
            ->get();

        $totalRevenue = $orders->sum('total_amount');
        $cashRevenue = $orders->where('payment_method', 'cash')->sum('total_amount');
        $qrisRevenue = $orders->where('payment_method', 'qris')->sum('total_amount');
        $roomChargeRevenue = $orders->where('payment_method', 'charge_to_room')->sum('total_amount');

        // Calculate total cost and profit
        $totalCost = 0;
        $totalProfit = 0;
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $itemCost = $item->quantity * $item->cost_price;
                $totalCost += $itemCost;
                $totalProfit += ($item->subtotal - $itemCost);
            }
        }

        // Top selling items
        $topItems = PosOrderItem::whereHas('posOrder', function ($q) use ($date) {
            $q->whereDate('created_at', $date->toDateString())
                ->where('status', 'completed');
        })
            ->selectRaw('item_name, SUM(quantity) as total_sold, SUM(subtotal) as total_revenue, SUM(quantity * cost_price) as total_cost')
            ->groupBy('item_name')
            ->orderByDesc('total_sold')
            ->limit(10)
            ->get();

        // Orders by hour
        $hourlyBreakdown = PosOrder::whereDate('created_at', $date->toDateString())
            ->where('status', 'completed')
            ->selectRaw('EXTRACT(HOUR FROM created_at) as hour, COUNT(*) as order_count, SUM(total_amount) as revenue')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        return [
            'date' => $date->toDateString(),
            'total_orders' => $orders->count(),
            'total_revenue' => round($totalRevenue, 2),
            'total_cost' => round($totalCost, 2),
            'total_profit' => round($totalProfit, 2),
            'cash_revenue' => round($cashRevenue, 2),
            'qris_revenue' => round($qrisRevenue, 2),
            'room_charge_revenue' => round($roomChargeRevenue, 2),
            'average_order_value' => $orders->count() > 0 ? round($totalRevenue / $orders->count(), 2) : 0,
            'top_items' => $topItems,
            'hourly_breakdown' => $hourlyBreakdown,
            'orders' => $orders,
        ];
    }

    /**
     * Cancel a POS order.
     *
     * @param PosOrder $order
     * @return PosOrder
     * @throws \Exception
     */
    public function cancelOrder(PosOrder $order): PosOrder
    {
        return DB::transaction(function () use ($order) {
            if ($order->payment_status === 'paid') {
                throw new \Exception('Cannot cancel a paid order. Process a refund instead.');
            }

            $order->update([
                'status' => 'cancelled',
            ]);

            \App\Models\AuditLog::log(
                'pos.order_cancelled',
                "POS order {$order->order_number} cancelled",
                $order
            );

            return $order->refresh();
        });
    }

    /**
     * Add an item to an existing POS order.
     *
     * @param PosOrder $order
     * @param array $itemData [inventory_id, item_name, quantity, price_per_unit]
     * @return PosOrderItem
     */
    public function addItemToOrder(PosOrder $order, array $itemData): PosOrderItem
    {
        return DB::transaction(function () use ($order, $itemData) {
            $quantity = $itemData['quantity'] ?? 1;
            $price = $itemData['price_per_unit'] ?? 0;
            $subtotal = $quantity * $price;

            // Get cost price from inventory if linked
            $costPrice = 0;
            if (isset($itemData['inventory_id'])) {
                $inventory = Inventory::find($itemData['inventory_id']);
                if ($inventory) {
                    $costPrice = $inventory->purchase_price ?? 0;
                }
            }

            $orderItem = PosOrderItem::create([
                'pos_order_id' => $order->id,
                'inventory_id' => $itemData['inventory_id'] ?? null,
                'item_name' => $itemData['item_name'] ?? 'Unknown Item',
                'quantity' => $quantity,
                'price_per_unit' => $price,
                'cost_price' => $costPrice,
                'subtotal' => $subtotal,
            ]);

            // Decrement inventory
            if (isset($itemData['inventory_id'])) {
                $inventory = Inventory::find($itemData['inventory_id']);
                if ($inventory) {
                    $this->updateInventory($inventory, $quantity);
                }
            }

            // Recalculate order totals
            $newSubtotal = $order->items()->sum('subtotal');
            $newTax = 0; // Tax - FORCED TO 0 (No tax as per user request)
            $newTotal = $newSubtotal + $newTax - $order->discount_amount;

            $order->update([
                'subtotal' => $newSubtotal,
                'tax_amount' => $newTax,
                'total_amount' => $newTotal,
            ]);

            return $orderItem;
        });
    }
}
