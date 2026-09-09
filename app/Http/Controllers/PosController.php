<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Inventory;
use App\Models\PosOrder;
use App\Models\Room;
use App\Services\PosService;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;

class PosController extends Controller
{
    use \App\Traits\AjaxResponse;

    protected PosService $posService;

    public function __construct(PosService $posService)
    {
        $this->posService = $posService;
    }

    /**
     * Shared filters between the order list and its date-range PDF export.
     */
    protected function filteredOrdersQuery(Request $request)
    {
        $query = PosOrder::with([
            'guest:id,name',
            'booking.room:id,room_number',
            'user:id,name',
            'items'
        ])->select('pos_orders.*');

        // Filter by payment status
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
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
                    ->orWhereHas('booking.room', fn($r) => $r->where('room_number', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    /**
     * Display a listing of POS orders.
     */
    public function index(Request $request)
    {
        $query = $this->filteredOrdersQuery($request);

        // Sortable columns (table header click); default stays category name then date
        $sortable = [
            'order_number' => 'pos_orders.order_number',
            'created_at' => 'pos_orders.created_at',
            'total_amount' => 'pos_orders.total_amount',
        ];
        $sort = $request->input('sort');
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';

        if ($sort && isset($sortable[$sort])) {
            $query->orderBy($sortable[$sort], $direction);
        } else {
            $query->leftJoin(\DB::raw('(
                SELECT poi.pos_order_id, MIN(ic.name) as category_name
                FROM pos_order_items poi
                JOIN inventories i ON i.id = poi.inventory_id
                JOIN inventory_categories ic ON ic.id = i.category_id
                GROUP BY poi.pos_order_id
            ) as order_cat'), 'order_cat.pos_order_id', '=', 'pos_orders.id')
                ->orderBy(\DB::raw('COALESCE(order_cat.category_name, \'Uncategorized\')'))
                ->orderBy('pos_orders.created_at', 'desc');
        }

        $orders = $query->paginate(15)->withQueryString();

        return view('pos.index', compact('orders'));
    }

    /**
     * Print/export the (filtered) order list for a date range -- same filters
     * as the index list, just without pagination.
     */
    public function exportPdf(Request $request)
    {
        $orders = $this->filteredOrdersQuery($request)
            ->orderBy('pos_orders.created_at', 'desc')
            ->get();

        $hotel = current_hotel();
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pos.orders_pdf', compact('orders', 'hotel', 'dateFrom', 'dateTo'));
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('pos_orders_' . now()->format('Ymd_His') . '.pdf');
    }

    /**
     * Show the form for creating a new POS order.
     */
    public function create(Request $request)
    {
        $inventoryItems = Inventory::where('is_active', true)
            ->orderBy('name')
            ->get();

        $guests = Guest::orderBy('name')->get();
        $occupiedBookings = Booking::with(['room.roomType', 'guest'])
            ->where('status', 'checked_in')
            ->get();

        $bankAccounts = \App\Models\BankAccount::where('hotel_id', active_hotel_id())
            ->where('is_active', true)
            ->get();

        // If booking_id is provided, load booking details
        $booking = null;
        if ($request->filled('booking_id')) {
            $booking = Booking::with(['guest', 'room.roomType'])
                ->where('status', 'checked_in')
                ->find($request->booking_id);
        }

        return view('pos.create', compact('inventoryItems', 'guests', 'occupiedBookings', 'booking', 'bankAccounts'));
    }

    /**
     * Store a newly created POS order in storage.
     */
    public function store(Request $request)
    {
        // "Charge to Room" is a sentinel option in the same <select> as real
        // bank accounts (not a real bank_accounts row) — normalize it before
        // validation, payment_method already carries 'charge_to_room' separately.
        if ($request->input('bank_account_id') === 'charge_to_room') {
            $request->merge(['bank_account_id' => null]);
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
            $orderData = [
                'guest_id' => $validated['guest_id'] ?? null,
                'room_id' => $validated['room_id'] ?? null,
                'booking_id' => $validated['booking_id'] ?? null,
                'bank_account_id' => $validated['bank_account_id'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'payment_method' => $validated['payment_method'] ?? null,
                'customer_paid' => $validated['customer_paid'] ?? null,
            ];

            $order = $this->posService->createOrder($orderData, $validated['items']);

            return $this->ajaxOrRedirect('POS order created successfully.', route('pos.show', $order));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified POS order.
     */
    public function show(PosOrder $order)
    {
        $order->load(['guest', 'booking.room.roomType', 'user', 'items.inventory']);

        $bankAccounts = \App\Models\BankAccount::where('hotel_id', active_hotel_id())
            ->where('is_active', true)
            ->get();
            
        $inventoryItems = \App\Models\Inventory::where('hotel_id', active_hotel_id())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('pos.show', compact('order', 'bankAccounts', 'inventoryItems'));
    }

    /**
     * Add an item to an existing POS order.
     */
    public function addItem(Request $request, PosOrder $order)
    {
        $validated = $request->validate([
            'inventory_id' => 'required|exists:inventories,id',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $inventory = \App\Models\Inventory::findOrFail($validated['inventory_id']);
            
            $this->posService->addItemToOrder($order, [
                'inventory_id' => $inventory->id,
                'item_name' => $inventory->name,
                'quantity' => $validated['quantity'],
                'price_per_unit' => $inventory->price_per_unit,
            ]);

            return $this->ajaxOrRedirect('Item added to order successfully.', route('pos.show', $order));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Process payment for a POS order.
     */
    public function processPayment(Request $request, PosOrder $order)
    {
        $validated = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $account = \App\Models\BankAccount::find($validated['bank_account_id']);
            $paymentMethod = str_contains(strtolower($account->name), 'tunai') ? 'cash' : 'bank_transfer';
            
            $this->posService->processPayment($order, $paymentMethod, $account->id, $validated['amount']);

            return $this->ajaxOrRedirect('Payment processed successfully.', route('pos.show', $order));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Delete an item from a POS order.
     */
    public function deleteItem(PosOrder $order, \App\Models\PosOrderItem $item)
    {
        $this->authorize('delete transactions');

        try {
            // If linked to a booking, check if there are subsequent payments
            if ($order->booking_id) {
                $booking = \App\Models\Booking::find($order->booking_id);
                $hasSubsequentPayment = $booking->transactions()
                    ->where('type', 'payment')
                    ->where('status', 'success')
                    ->where('created_at', '>=', $item->created_at)
                    ->exists();
                
                if ($hasSubsequentPayment || $booking->status === 'checked_out') {
                    $errorMsg = 'Tidak dapat menghapus item: Item ini sudah terkunci oleh transaksi pembayaran.';
                    if ($this->isAjaxRequest()) {
                        return $this->ajaxError($errorMsg);
                    }
                    return redirect()->back()
                        ->with('error', $errorMsg);
                }
            }

            $this->posService->deleteItemFromOrder($item);

            return $this->ajaxOrRedirect('Item removed successfully.', redirect()->back()->getTargetUrl());
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Print POS receipt.
     */
    public function printOrder(PosOrder $order)
    {
        $order->load(['guest', 'booking.room.roomType', 'user', 'items.inventory', 'hotel']);
        return view('pos.print_receipt', compact('order'));
    }

    /**
     * Charge a POS order to a room booking.
     */
    public function chargeToRoom(Request $request, PosOrder $order)
    {
        $validated = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
        ]);

        $booking = Booking::findOrFail($validated['booking_id']);

        try {
            $this->posService->chargeToRoomBooking($order, $booking);

            return $this->ajaxOrRedirect('Order charged to room successfully.', route('pos.show', $order));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    public function destroy(PosOrder $order)
    {
        $this->authorize('delete transactions');

        try {
            if ($order->payment_status === 'paid') {
                return redirect()->back()->with('error', 'Cannot delete a paid order.');
            }

            $order->items()->delete();
            $order->delete();

            return redirect()->route('pos.index')->with('success', 'Order deleted.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
