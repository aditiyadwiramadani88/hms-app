<?php

namespace App\Http\Controllers;

use App\Models\CustomInvoice;
use App\Models\Booking;
use App\Models\Room;
use App\Models\Guest;
use App\Models\BankAccount;
use App\Models\Transaction;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomInvoiceController extends Controller
{
    use AjaxResponse;

    public function index(Request $request)
    {
        $query = CustomInvoice::with(['guest', 'booking']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('guest', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        if ($request->filled('date_from')) {
            $query->where('check_in', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('check_out', '<=', $request->date_to);
        }

        $invoices = $query->latest()->paginate(20)->withQueryString();

        $sources = CustomInvoice::select('source')->distinct()->pluck('source');

        return view('custom-invoices.index', compact('invoices', 'sources'));
    }

    public function create()
    {
        $bankAccounts = BankAccount::where('hotel_id', active_hotel_id())->where('is_active', true)->get();
        $rooms = Room::where('hotel_id', active_hotel_id())->available()->get();

        return view('custom-invoices.create', compact('bankAccounts', 'rooms'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'guest_id' => 'required|exists:guests,id',
            'source' => 'required|string|max:50',
            'room_name' => 'required|string|max:100',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'adults' => 'required|integer|min:1',
            'children' => 'required|integer|min:0',
            'sell_price' => 'required|numeric|min:0',
            'agent_commission' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'create_booking' => 'nullable|boolean',
            'room_id' => 'nullable|required_if:create_booking,1|exists:rooms,id',
            'down_payment' => 'nullable|numeric|min:0',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
        ]);

        if (($validated['down_payment'] ?? 0) > 0 && empty($validated['bank_account_id'])) {
            return back()->withErrors(['bank_account_id' => 'Akun pembayaran wajib dipilih jika ada nominal bayar.'])->withInput();
        }

        try {
            $result = DB::transaction(function () use ($validated) {
                $checkIn = \Carbon\Carbon::parse($validated['check_in']);
                $checkOut = \Carbon\Carbon::parse($validated['check_out']);
                $nights = max(1, $checkIn->diffInDays($checkOut));

                $invoiceNumber = CustomInvoice::generateInvoiceNumber(active_hotel_id());

                $invoice = CustomInvoice::create([
                    'hotel_id' => active_hotel_id(),
                    'invoice_number' => $invoiceNumber,
                    'guest_id' => $validated['guest_id'],
                    'source' => $validated['source'],
                    'room_name' => $validated['room_name'],
                    'check_in' => $validated['check_in'],
                    'check_out' => $validated['check_out'],
                    'nights' => $nights,
                    'adults' => $validated['adults'],
                    'children' => $validated['children'] ?? 0,
                    'sell_price' => $validated['sell_price'],
                    'agent_commission' => $validated['agent_commission'] ?? 0,
                    'notes' => $validated['notes'] ?? null,
                    'status' => 'draft',
                    'created_by' => auth()->id(),
                ]);

                if (!empty($validated['create_booking'])) {
                    $booking = Booking::create([
                        'hotel_id' => active_hotel_id(),
                        'guest_id' => $validated['guest_id'],
                        'room_id' => $validated['room_id'],
                        'is_custom' => true,
                        'custom_room_name' => $validated['room_name'],
                        'user_id' => auth()->id(),
                        'check_in' => $checkIn,
                        'check_out' => $checkOut,
                        'adults' => $validated['adults'],
                        'children' => $validated['children'] ?? 0,
                        'base_price' => $validated['sell_price'],
                        'total_price' => $validated['sell_price'],
                        'status' => 'confirmed',
                        'payment_status' => 'unpaid',
                        'stay_type' => 'daily',
                        'source' => 'direct',
                        'guest_type' => 'umum',
                        'notes' => "[CUSTOM INVOICE #{$invoiceNumber}] " . ($validated['notes'] ?? ''),
                    ]);

                    Transaction::create([
                        'hotel_id' => active_hotel_id(),
                        'booking_id' => $booking->id,
                        'guest_id' => $validated['guest_id'],
                        'user_id' => auth()->id(),
                        'type' => 'charge',
                        'amount' => $validated['sell_price'],
                        'reference_id' => $invoiceNumber,
                        'description' => "Custom Invoice: {$validated['room_name']}",
                        'status' => 'success',
                    ]);

                    $invoice->update(['booking_id' => $booking->id]);
                }

                if (($validated['down_payment'] ?? 0) > 0) {
                    $account = BankAccount::find($validated['bank_account_id']);
                    if (!$account) {
                        throw new \Exception("Akun pembayaran tidak ditemukan.");
                    }

                    $paymentMethod = str_contains(strtolower($account->name), 'tunai') ? 'cash' : 'bank_transfer';

                    Transaction::create([
                        'hotel_id' => active_hotel_id(),
                        'guest_id' => $validated['guest_id'],
                        'user_id' => auth()->id(),
                        'bank_account_id' => $account->id,
                        'type' => 'payment',
                        'amount' => $validated['down_payment'],
                        'payment_method' => $paymentMethod,
                        'description' => "Payment for Custom Invoice {$invoiceNumber}",
                        'status' => 'success',
                    ]);

                    $account->increment('balance', $validated['down_payment']);

                    $invoice->update([
                        'payment_method' => $paymentMethod,
                        'paid_at' => now(),
                        'status' => 'paid',
                    ]);
                }

                return $invoice;
            });

            return $this->ajaxOrRedirect(
                'Custom invoice created successfully.',
                route('custom-invoices.index'),
                $result,
                201
            );
        } catch (\Throwable $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function show(CustomInvoice $customInvoice)
    {
        $customInvoice->load(['guest', 'booking', 'creator']);
        return view('custom-invoices.show', compact('customInvoice'));
    }

    public function edit(CustomInvoice $customInvoice)
    {
        $customInvoice->load(['guest', 'booking']);
        $bankAccounts = BankAccount::where('hotel_id', active_hotel_id())->where('is_active', true)->get();
        $rooms = Room::where('hotel_id', active_hotel_id())->available()->get();

        return view('custom-invoices.edit', compact('customInvoice', 'bankAccounts', 'rooms'));
    }

    public function update(Request $request, CustomInvoice $customInvoice)
    {
        $validated = $request->validate([
            'guest_id' => 'required|exists:guests,id',
            'source' => 'required|string|max:50',
            'room_name' => 'required|string|max:100',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'adults' => 'required|integer|min:1',
            'children' => 'required|integer|min:0',
            'sell_price' => 'required|numeric|min:0',
            'agent_commission' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            $checkIn = \Carbon\Carbon::parse($validated['check_in']);
            $checkOut = \Carbon\Carbon::parse($validated['check_out']);
            $nights = max(1, $checkIn->diffInDays($checkOut));

            $customInvoice->update([
                'guest_id' => $validated['guest_id'],
                'source' => $validated['source'],
                'room_name' => $validated['room_name'],
                'check_in' => $validated['check_in'],
                'check_out' => $validated['check_out'],
                'nights' => $nights,
                'adults' => $validated['adults'],
                'children' => $validated['children'] ?? 0,
                'sell_price' => $validated['sell_price'],
                'agent_commission' => $validated['agent_commission'] ?? 0,
                'notes' => $validated['notes'] ?? null,
            ]);

            return $this->ajaxOrRedirect(
                'Custom invoice updated successfully.',
                route('custom-invoices.show', $customInvoice->id),
                $customInvoice
            );
        } catch (\Throwable $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function destroy(CustomInvoice $customInvoice)
    {
        $customInvoice->delete();

        return $this->ajaxOrRedirect(
            'Custom invoice deleted.',
            route('custom-invoices.index')
        );
    }

    public function markPaid(Request $request, CustomInvoice $customInvoice)
    {
        $validated = $request->validate([
            'payment_method' => 'required|string|max:50',
        ]);

        $customInvoice->update([
            'status' => 'paid',
            'payment_method' => $validated['payment_method'],
            'paid_at' => now(),
        ]);

        return $this->ajaxOrRedirect(
            'Invoice marked as paid.',
            route('custom-invoices.show', $customInvoice->id),
            $customInvoice
        );
    }

    public function markSent(CustomInvoice $customInvoice)
    {
        $customInvoice->update(['status' => 'sent']);

        return $this->ajaxOrRedirect(
            'Invoice marked as sent.',
            route('custom-invoices.show', $customInvoice->id),
            $customInvoice
        );
    }

    public function markCancelled(CustomInvoice $customInvoice)
    {
        $customInvoice->update(['status' => 'cancelled']);

        return $this->ajaxOrRedirect(
            'Invoice cancelled.',
            route('custom-invoices.show', $customInvoice->id),
            $customInvoice
        );
    }

    public function print(CustomInvoice $customInvoice)
    {
        $customInvoice->load(['guest', 'booking', 'creator']);
        $hotel = current_hotel();

        return view('custom-invoices.print', compact('customInvoice', 'hotel'));
    }
}
