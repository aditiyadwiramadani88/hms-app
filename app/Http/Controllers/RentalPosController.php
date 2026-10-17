<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Inventory;
use App\Models\InventoryCategory;
use App\Models\PosOrder;
use App\Services\PosService;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;

class RentalPosController extends Controller
{
    use AjaxResponse;

    protected PosService $posService;

    public function __construct(PosService $posService)
    {
        $this->posService = $posService;
    }

    /**
     * Query rental inventory items (category: SEWA / Rental)
     * with active rented count and total capacity calculation.
     */
    protected function getRentalInventoryItems()
    {
        $items = Inventory::where('hotel_id', active_hotel_id())
            ->where('is_active', true)
            ->where('price_per_unit', '>', 0)
            ->whereHas('inventoryCategory', function ($q) {
                $q->whereRaw('UPPER(name) = ?', ['SEWA'])
                  ->orWhereRaw('UPPER(name) LIKE ?', ['%RENTAL%'])
                  ->orWhereRaw('UPPER(name) LIKE ?', ['%SEWA%']);
            })
            ->with('inventoryCategory')
            ->orderBy('name')
            ->get();

        $items->each(function ($item) {
            $item->rented_count = (int) \App\Models\PosOrderItem::where('inventory_id', $item->id)
                ->whereHas('posOrder', function ($q) {
                    $q->where('rental_status', 'rented');
                })
                ->sum('quantity');
            $item->total_capacity = $item->stock + $item->rented_count;
        });

        return $items;
    }

    /**
     * Display a listing of Rental POS orders.
     */
    public function index(Request $request)
    {
        $query = PosOrder::where('hotel_id', active_hotel_id())
            ->where(function ($q) {
                $q->where('order_type', 'rental')
                  ->orWhereHas('items.inventory.inventoryCategory', function ($cq) {
                      $cq->whereRaw('UPPER(name) = ?', ['SEWA'])
                         ->orWhereRaw('UPPER(name) LIKE ?', ['%RENTAL%'])
                         ->orWhereRaw('UPPER(name) LIKE ?', ['%SEWA%']);
                  });
            })
            ->with([
                'guest:id,name',
                'booking.room:id,room_number',
                'user:id,name',
                'returnedBy:id,name',
                'items.inventory'
            ]);

        // Filter by payment status
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by order status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by rental status (rented or returned)
        if ($request->filled('rental_status')) {
            $query->where('rental_status', $request->rental_status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search by order number, guest name, or room number
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('guest', fn($g) => $g->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('room', fn($r) => $r->where('room_number', 'like', "%{$search}%"))
                    ->orWhereHas('booking.guest', fn($g) => $g->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('booking.room', fn($r) => $r->where('room_number', 'like', "%{$search}%"))
                    ->orWhereHas('items', fn($i) => $i->where('item_name', 'like', "%{$search}%"));
            });
        }

        $orders = $query->latest('id')->paginate(15)->withQueryString();

        // Calculate summary KPIs
        $baseRentalQuery = PosOrder::where('hotel_id', active_hotel_id())
            ->where(function ($q) {
                $q->where('order_type', 'rental')
                  ->orWhereHas('items.inventory.inventoryCategory', function ($cq) {
                      $cq->whereRaw('UPPER(name) = ?', ['SEWA']);
                  });
            });

        $totalOrders = (clone $baseRentalQuery)->count();
        $activeRentals = (clone $baseRentalQuery)->where('rental_status', 'rented')->count();
        $returnedRentals = (clone $baseRentalQuery)->where('rental_status', 'returned')->count();
        $totalRevenue = (clone $baseRentalQuery)->where('payment_status', 'paid')->sum('total_amount');

        return view('pos.rental.index', compact('orders', 'totalOrders', 'activeRentals', 'returnedRentals', 'totalRevenue'));
    }

    /**
     * Show the form for creating a new Rental POS order.
     */
    public function create(Request $request)
    {
        $inventoryItems = $this->getRentalInventoryItems();

        $categories = InventoryCategory::where('hotel_id', active_hotel_id())
            ->where(function ($q) {
                $q->whereRaw('UPPER(name) = ?', ['SEWA'])
                  ->orWhereRaw('UPPER(name) LIKE ?', ['%RENTAL%'])
                  ->orWhereRaw('UPPER(name) LIKE ?', ['%SEWA%']);
            })
            ->get();

        $guests = Guest::where('hotel_id', active_hotel_id())
            ->orderBy('name')
            ->get();

        $occupiedBookings = Booking::where('hotel_id', active_hotel_id())
            ->with(['room.roomType', 'guest'])
            ->where('status', 'checked_in')
            ->get();

        $bankAccounts = \App\Models\BankAccount::where('hotel_id', active_hotel_id())
            ->where('is_active', true)
            ->get();

        $booking = null;
        if ($request->filled('booking_id')) {
            $booking = Booking::where('hotel_id', active_hotel_id())
                ->with(['guest', 'room.roomType'])
                ->where('status', 'checked_in')
                ->find($request->booking_id);
        }

        return view('pos.rental.create', compact(
            'inventoryItems',
            'categories',
            'guests',
            'occupiedBookings',
            'booking',
            'bankAccounts'
        ));
    }

    /**
     * Store a newly created Rental POS order in storage.
     */
    public function store(Request $request)
    {
        // Normalize "Charge to Room" sentinel option
        if ($request->input('bank_account_id') === 'charge_to_room') {
            $request->merge(['bank_account_id' => null, 'payment_method' => 'charge_to_room']);
        }

        $validated = $request->validate([
            'guest_id' => 'nullable|exists:guests,id',
            'room_id' => 'nullable|exists:rooms,id',
            'booking_id' => 'nullable|exists:bookings,id',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'notes' => 'nullable|string|max:500',
            'discount_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:cash,credit_card,qris,charge_to_room,bank_transfer',
            'customer_paid' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.inventory_id' => 'nullable|exists:inventories,id',
            'items.*.item_name' => 'required_without:items.*.inventory_id|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price_per_unit' => 'required|numeric|min:0',
        ]);

        try {
            // Auto-resolve room_id and guest_id from booking_id if booking_id was provided
            if (!empty($validated['booking_id'])) {
                $booking = Booking::find($validated['booking_id']);
                if ($booking) {
                    $validated['room_id'] = $validated['room_id'] ?: $booking->room_id;
                    $validated['guest_id'] = $validated['guest_id'] ?: $booking->guest_id;
                }
            }

            // Flag as rental order
            $validated['order_type'] = 'rental';
            if (empty($validated['notes'])) {
                $validated['notes'] = 'Rental POS order';
            }

            $order = $this->posService->createOrder($validated, $validated['items']);

            // If charge to room was selected with a booking, process it
            if (($validated['payment_method'] ?? null) === 'charge_to_room' && !empty($order->booking_id)) {
                $booking = Booking::find($order->booking_id);
                if ($booking) {
                    $this->posService->chargeToRoomBooking($order, $booking);
                }
            }

            return $this->ajaxOrRedirect('Rental order created successfully.', route('pos.rental.index'), $order, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError('Validation failed', $e->errors());
            }
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError('Failed to create rental order: ' . $e->getMessage());
            }
            return back()->with('error', 'Failed to create rental order: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Process return of rented items for a Rental POS order.
     * Restores inventory and warehouse stock, marks order as returned.
     */
    public function returnRental(Request $request, PosOrder $order)
    {
        if ($order->order_type !== 'rental') {
            return back()->with('error', 'Order ini bukan merupakan transaksi rental.');
        }

        if ($order->rental_status === 'returned') {
            return back()->with('warning', 'Barang rental dari order ini sudah dikembalikan sebelumnya.');
        }

        $validated = $request->validate([
            'return_notes' => 'nullable|string|max:500',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($order, $validated) {
            $order->load('items.inventory');
            $primaryWarehouse = \App\Models\Warehouse::where('hotel_id', $order->hotel_id)->orderBy('id')->first();

            foreach ($order->items as $item) {
                if ($item->inventory_id) {
                    // Restore available stock on inventory
                    \App\Models\Inventory::where('id', $item->inventory_id)->increment('stock', $item->quantity);

                    // Restore warehouse stock
                    if ($primaryWarehouse) {
                        $whStock = \App\Models\InventoryWarehouseStock::firstOrCreate([
                            'inventory_id' => $item->inventory_id,
                            'warehouse_id' => $primaryWarehouse->id,
                        ], ['stock' => 0]);
                        $whStock->increment('stock', $item->quantity);
                    }

                    // Record inventory mutation (IN / Pengembalian)
                    \App\Models\InventoryMutation::create([
                        'hotel_id' => $order->hotel_id,
                        'inventory_id' => $item->inventory_id,
                        'warehouse_id' => $primaryWarehouse ? $primaryWarehouse->id : null,
                        'type' => 'in',
                        'quantity' => $item->quantity,
                        'reference_type' => 'pos_order_return',
                        'reference_id' => $order->id,
                        'notes' => 'Pengembalian barang rental order #' . $order->order_number . (!empty($validated['return_notes']) ? ': ' . $validated['return_notes'] : ''),
                        'user_id' => \Illuminate\Support\Facades\Auth::id(),
                    ]);

                    $item->update([
                        'returned_quantity' => $item->quantity,
                        'returned_at' => now(),
                    ]);
                }
            }

            $order->update([
                'rental_status' => 'returned',
                'returned_at' => now(),
                'returned_by' => \Illuminate\Support\Facades\Auth::id(),
                'return_notes' => $validated['return_notes'] ?? null,
            ]);
        });

        return $this->ajaxOrRedirect('Barang rental order #' . $order->order_number . ' berhasil dikembalikan ke stok.', route('pos.rental.index'));
    }
}
