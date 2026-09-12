<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Room;
use App\Models\Transaction;
use App\Services\BookingPriceService;
use App\Services\BookingService;
use App\Services\PricingService;
use App\Services\WhatsAppService;
use App\Traits\AjaxResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    use \App\Traits\AjaxResponse;

    protected $bookingService;
    protected $pricingService;
    protected $waService;

    public function __construct(
        BookingService $bookingService,
        PricingService $pricingService,
        WhatsAppService $waService,
    ) {
        $this->bookingService = $bookingService;
        $this->pricingService = $pricingService;
        $this->waService = $waService;
    }

    /**
     * Handle global search from navbar.
     */
    public function globalSearch(Request $request)
    {
        $term = $request->query("q");
        if (!$term) {
            return redirect()->back();
        }

        $booking = Booking::where("id", $term)->first();

        if ($booking) {
            return redirect()->route("bookings.show", $booking->id);
        }

        return redirect()
            ->route("bookings.index", ["search" => $term])
            ->with("error", "Booking with code or ID '{$term}' not found.");
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Booking::with([
            "guest:id,name",
            "room.roomType:id,name",
            "bookingSource:id,name",
        ])
            ->select("bookings.*")
            ->addSelect([
                'unpaid_pos_total' => \App\Models\PosOrder::selectRaw('COALESCE(SUM(total_amount), 0)')
                    ->whereColumn('booking_id', 'bookings.id')
                    ->where('status', 'completed')
                    ->where('payment_status', 'unpaid')
                    ->limit(1),
                'manual_extra_total' => \App\Models\Transaction::selectRaw('COALESCE(SUM(amount), 0)')
                    ->whereColumn('booking_id', 'bookings.id')
                    ->where('type', 'charge')
                    ->where('status', 'success')
                    ->whereNull('reference_id')
                    ->where('is_deposit', false)
                    ->limit(1),
                'total_deposit' => \App\Models\Transaction::selectRaw('COALESCE(SUM(amount), 0)')
                    ->whereColumn('booking_id', 'bookings.id')
                    ->where('type', 'charge')
                    ->where('is_deposit', true)
                    ->limit(1),
                'total_payments' => \App\Models\Transaction::selectRaw('COALESCE(SUM(amount), 0)')
                    ->whereColumn('booking_id', 'bookings.id')
                    ->where('type', 'payment')
                    ->where('status', 'success')
                    ->limit(1),
            ]);

        $tab = $request->get("tab", "today");

        // Default Date Range
        $defaultStart = now()->toDateString(); // default to today
        $defaultEnd = now()->toDateString(); // default to today

        // For Cancelled, default to current month range
        if ($tab === "cancelled") {
            $defaultStart = now()->startOfMonth()->toDateString();
            $defaultEnd = now()->endOfMonth()->toDateString();
        }

        $startDate = $request->get("start_date", $defaultStart);
        $endDate = $request->get("end_date", $defaultEnd);

        if ($request->filled("search")) {
            $search = $request->search;
            $query
                ->whereHas("guest", function ($q) use ($search) {
                    $q->where("name", "like", "%{$search}%");
                })
                ->orWhere("id", "like", "%{$search}%");
        }

        if ($request->filled("status")) {
            $query->where("bookings.status", $request->status);
        }

        if ($request->filled("payment_status")) {
            $query->where("bookings.payment_status", $request->payment_status);
        }

        if ($request->filled("room_id")) {
            $query->where("bookings.room_id", $request->room_id);
        }

        if ($request->filled("checkout_date")) {
            $query->whereDate("bookings.check_out", $request->checkout_date);
        }

        // Tab filtering
        // Use actual calendar date (not business date) for booking tabs
        $today = now()->toDateString();

        if ($tab === "today") {
            // Today: status bukan checked_in/checked_out/cancelled/no_show, check_in = today (no date range)
            $query
                ->whereDate("bookings.check_in", $today)
                ->whereNotIn("bookings.status", ["checked_in", "checked_out", "cancelled", "no_show"]);
        } elseif ($tab === "checkin") {
            // Check-in / In House: status = checked_in. Tampilkan yang overlap range
            // (check_in <= end_date AND check_out >= start_date) ATAU yang masih benar-
            // benar in-house (actual_check_out belum terisi) -- termasuk overstay yang
            // tanggal check_out booking-nya sudah lewat. Tanpa kondisi kedua, tamu
            // overstay hilang dari list (default range = hari ini) dan cuma ketemu lewat
            // search.
            $query
                ->where("bookings.status", "checked_in")
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereNull("bookings.actual_check_out")
                        ->orWhere(function ($rq) use ($startDate, $endDate) {
                            $rq->whereDate("bookings.check_in", "<=", $endDate)
                                ->whereDate("bookings.check_out", ">=", $startDate);
                        });
                });
        } elseif ($tab === "checkout") {
            // Checkout: status = checked_out, check_out dalam date range
            $query
                ->where("bookings.status", "checked_out")
                ->whereDate("bookings.check_out", ">=", $startDate)
                ->whereDate("bookings.check_out", "<=", $endDate);

        } elseif ($tab === "cancelled") {
            // Cancelled: status cancelled/no_show, check_in dalam date range
            $query
                ->whereIn("bookings.status", ["cancelled", "no_show"])
                ->whereDate("bookings.check_in", ">=", $startDate)
                ->whereDate("bookings.check_in", "<=", $endDate);
        } elseif ($tab === "all") {
            // All: semua booking confirmed kecuali yang check-in hari ini (pindah ke Today)
            $query->where("bookings.status", "confirmed");
            $query->whereDate("bookings.check_in", "!=", $today);
        }

        // Sorting — All tab defaults to created_at desc (tanggal booking terbaru)
        $sortBy = $request->get(
            "sort_by",
            $tab === "all" ? "created_at" : "check_in",
        );
        $sortDir = $request->get("sort_dir", "desc");
        $sortDir = in_array($sortDir, ["asc", "desc"]) ? $sortDir : "desc";

        if ($sortBy === "room_number") {
            $query
                ->leftJoin("rooms", "bookings.room_id", "=", "rooms.id")
                ->orderBy("rooms.room_number", $sortDir);
        } elseif ($sortBy === "guest_name") {
            $query
                ->leftJoin("guests", "bookings.guest_id", "=", "guests.id")
                ->orderBy("guests.name", $sortDir);
        } elseif ($sortBy === "id") {
            $query->orderBy("id", $sortDir);
        } elseif ($sortBy === "status") {
            $query->orderBy("bookings.status", $sortDir);
        } elseif ($sortBy === "payment_status") {
            $query->orderBy("bookings.payment_status", $sortDir);
        } elseif ($sortBy === "total_price") {
            $query->orderBy("bookings.total_price", $sortDir);
        } elseif ($sortBy === "check_out") {
            $query->orderBy("bookings.check_out", $sortDir);
        } elseif ($sortBy === "source") {
            $query->orderBy("bookings.source", $sortDir);
        } elseif ($sortBy === "created_at") {
            $query->orderBy("bookings.created_at", $sortDir);
        } else {
            $query->orderBy("bookings.check_in", $sortDir);
        }

        $bookings = $query->paginate(15)->withQueryString();

        // Counts for tabs
        $counts = [
            "today" => Booking::whereDate("check_in", $today)
                ->whereNotIn("status", ["checked_in", "checked_out", "cancelled", "no_show"])
                ->count(),
            "checkin" => Booking::where("status", "checked_in")
                ->where(function ($q) use ($today) {
                    $q->whereNull("actual_check_out")
                        ->orWhere(function ($rq) use ($today) {
                            $rq->whereDate("check_in", "<=", $today)
                                ->whereDate("check_out", ">=", $today);
                        });
                })
                ->count(),
            "checkout" => Booking::where("status", "checked_out")
                ->whereDate("check_out", ">=", $startDate)
                ->whereDate("check_out", "<=", $endDate)
                ->count(),

            "cancelled" => Booking::whereIn("status", ["cancelled", "no_show"])
                ->whereDate("check_in", ">=", $startDate)
                ->whereDate("check_in", "<=", $endDate)
                ->count(),
            "all" => Booking::where("status", "confirmed")->count(),
        ];

        $roomsList = \App\Models\Room::orderBy("room_number")->get();

        return view(
            "bookings.index",
            compact("bookings", "counts", "roomsList", "startDate", "endDate"),
        );
    }

    /**
     * Show the form for creating a new booking.
     */
    public function create(Request $request)
    {
        $defaultCheckIn = get_hotel_date();
        $defaultCheckOut = date(
            "Y-m-d",
            strtotime($defaultCheckIn . " +1 day"),
        );

        $request->merge([
            "check_in" => $request->query("check_in", $defaultCheckIn),
            "check_out" => $request->query("check_out", $defaultCheckOut),
        ]);

        $roomTypes = \App\Models\RoomType::where("is_active", true)->get();
        $guestCategories = \App\Models\GuestCategory::all([
            "id",
            "name",
            "breakfast_price",
        ]);
        $rooms = collect();
        $taxPercentage =
            \App\Models\Hotel::find(active_hotel_id())->tax_percentage ?? 0;

        return view(
            "bookings.create",
            compact("rooms", "roomTypes", "taxPercentage", "guestCategories"),
        );
    }

    /**
     * Show the form for creating a new custom (markup) booking.
     */
    public function createCustom(Request $request)
    {
        $defaultCheckIn = get_hotel_date();
        $defaultCheckOut = date(
            "Y-m-d",
            strtotime($defaultCheckIn . " +1 day"),
        );

        $request->merge([
            "check_in" => $request->query("check_in", $defaultCheckIn),
            "check_out" => $request->query("check_out", $defaultCheckOut),
        ]);

        $bankAccounts = \App\Models\BankAccount::where(
            "hotel_id",
            active_hotel_id(),
        )
            ->where("is_active", true)
            ->get();

        return view("bookings.create_custom", compact("bankAccounts"));
    }

    /**
     * Store a custom (markup) booking.
     */
    public function storeCustom(Request $request)
    {
        $validated = $request->validate([
            "guest_id" => "required|exists:guests,id",
            "custom_room_name" => "required|string|max:255",
            "check_in" =>
                "required|date" .
                (auth()->user()->can("manage reservations") ||
                auth()->user()->can("bookings.create")
                    ? ""
                    : "|after_or_equal:today"),
            "check_out" => "required|date|after:check_in",
            "total_price" => "required|numeric|min:0", // Harga di Invoice (Markup)
            "real_price" => "required|numeric|min:0", // Harga Asli Hotel (Report)
            "down_payment" => "nullable|numeric|min:0",
            "bank_account_id" =>
                "nullable|required_with:down_payment|exists:bank_accounts,id",
            "notes" => "nullable|string",
        ]);

        try {
            $booking = DB::transaction(function () use ($validated) {
                $checkIn = \Carbon\Carbon::parse($validated["check_in"]);
                $checkOut = \Carbon\Carbon::parse($validated["check_out"]);
                $nights = max(1, $checkIn->diffInDays($checkOut));

                // Prevent OOM / CPU timeout if user accidentally inputs a massive date difference (e.g. year 3026)
                if ($nights > 365) {
                    throw new \Exception(
                        "Maksimal rentang durasi custom booking adalah 365 malam. Harap periksa kembali tanggal Check Out.",
                    );
                }

                // Markup amount
                $markupTotal =
                    $validated["total_price"] - $validated["real_price"];
                $pricePerNightMarkup = $validated["total_price"] / $nights;

                $breakdown = [];
                $tempDate = $checkIn->copy();
                for ($i = 0; $i < $nights; $i++) {
                    $breakdown[] = [
                        "date" => $tempDate->toDateString(),
                        "day_of_week" => $tempDate->format("l"),
                        "price" => round($pricePerNightMarkup, 2),
                    ];
                    $tempDate->addDay();
                }

                $internalNotes =
                    "[MARKUP REPORT] Harga Asli: Rp" .
                    number_format((float) $validated["real_price"]) .
                    " | Markup: Rp" .
                    number_format((float) $markupTotal) .
                    "\n" .
                    ($validated["notes"] ?? "");

                $booking = \App\Models\Booking::create([
                    "hotel_id" => active_hotel_id(),
                    "guest_id" => $validated["guest_id"],
                    "room_id" => null,
                    "is_custom" => true,
                    "custom_room_name" => $validated["custom_room_name"],
                    "user_id" => auth()->id(),
                    "check_in" => $checkIn,
                    "check_out" => $checkOut,
                    "adults" => 1,
                    "children" => 0,
                    "base_price" => $validated["real_price"], // Masuk ke laporan sebagai Harga Asli
                    "total_price" => $validated["total_price"], // Tampil di Invoice
                    "status" => "confirmed",
                    "payment_status" => ($validated["total_price"] ?? 0) <= 0 ? "paid" : "unpaid",
                    "stay_type" => "daily",
                    "source" => "direct",
                    "guest_type" => "umum",
                    "notes" => $internalNotes,
                    "pricing_breakdown" => $breakdown,
                ]);

                // Create initial transaction record
                \App\Models\Transaction::create([
                    "hotel_id" => active_hotel_id(),
                    "booking_id" => $booking->id,
                    "guest_id" => $booking->guest_id,
                    "user_id" => auth()->id(),
                    "type" => "charge",
                    "amount" => $booking->total_price,
                    "reference_id" => "BOOK-" . $booking->id,
                    "description" =>
                        "Custom Booking: " .
                        $booking->custom_room_name .
                        " (Invoice Value)",
                    "status" => "success",
                ]);

                // Add Internal Markup Transaction (as markup type to exclude from real revenue)
                if ($markupTotal > 0) {
                    \App\Models\Transaction::create([
                        "hotel_id" => active_hotel_id(),
                        "booking_id" => $booking->id,
                        "guest_id" => $booking->guest_id,
                        "user_id" => auth()->id(),
                        "type" => "charge",
                        "amount" => $markupTotal,
                        "is_markup" => true,
                        "description" => "[MARKUP] Selisih harga untuk tamu",
                        "status" => "success",
                    ]);
                }

                // Handle Down Payment if provided
                if (($validated["down_payment"] ?? 0) > 0) {
                    $account = \App\Models\BankAccount::find(
                        $validated["bank_account_id"] ?? null,
                    );
                    if (!$account) {
                        throw new \Exception(
                            "Akun pembayaran tidak ditemukan. Pastikan akun tersedia untuk properti ini.",
                        );
                    }

                    $paymentMethod = str_contains(
                        strtolower($account->name),
                        "tunai",
                    )
                        ? "cash"
                        : "bank_transfer";

                    \App\Models\Transaction::create([
                        "hotel_id" => active_hotel_id(),
                        "booking_id" => $booking->id,
                        "guest_id" => $booking->guest_id,
                        "user_id" => auth()->id(),
                        "bank_account_id" => $account->id,
                        "type" => "payment",
                        "amount" => $validated["down_payment"],
                        "payment_method" => $paymentMethod,
                        "description" =>
                            "Initial payment for custom booking #" .
                            $booking->id,
                        "status" => "success",
                        "is_realized" => false,
                    ]);

                    $account->increment("balance", $validated["down_payment"]);

                    if ($validated["down_payment"] >= $booking->total_price) {
                        $booking->update(["payment_status" => "paid"]);
                    } else {
                        $booking->update(["payment_status" => "partial"]);
                    }
                }

                return $booking;
            });

            return $this->ajaxOrRedirect(
                "Custom (Markup) Booking created successfully.",
                route("bookings.show", $booking->id),
                $booking,
                201,
            );
        } catch (\Throwable $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return back()->with("error", $e->getMessage())->withInput();
        }
    }

    /**
     * Get available rooms via AJAX.
     */
    public function getAvailableRooms(Request $request)
    {
        $checkIn = $request->query("check_in");
        $checkOut = $request->query("check_out");
        $checkInTime = $request->query("check_in_time");
        $checkOutTime = $request->query("check_out_time");
        $roomTypeId = $request->query("room_type_id");
        $guestId = $request->query("guest_id");
        $stayType = $request->query("stay_type", "daily");
        $includeUnavailable = $request->boolean("include_unavailable");

        $guest = $guestId ? Guest::find($guestId) : null;

        if (!$checkIn || !$checkOut) {
            $availableStatuses = \App\Models\RoomStatus::where(
                "is_available",
                true,
            )
                ->pluck("name")
                ->toArray();
            $rooms = \App\Models\Room::with("roomType")
                ->when(!$includeUnavailable, fn($q) => $q->whereIn("status", $availableStatuses))
                ->when(
                    $roomTypeId,
                    fn($q) => $q->where("room_type_id", $roomTypeId),
                )
                ->when(
                    $stayType === "monthly",
                    fn($q) => $q->where("is_kos", true),
                )
                ->orderByRaw('CAST(room_number AS UNSIGNED) ASC')
                ->get();
        } else {
            $rooms = $this->bookingService->getAvailableRooms(
                $checkIn,
                $checkOut,
                $roomTypeId,
                $stayType,
                $includeUnavailable,
                $checkInTime,
                $checkOutTime,
            );
        }

        $checkInDate = $checkIn
            ? \Carbon\Carbon::parse($checkIn)
            : \Carbon\Carbon::today();
        $checkOutDate = $checkOut ? \Carbon\Carbon::parse($checkOut) : null;
        $newCheckInDT = $checkOutDate ? $this->bookingService->combineDateAndTime($checkInDate, $checkInTime, '14:00') : null;
        $newCheckOutDT = $checkOutDate ? $this->bookingService->combineDateAndTime($checkOutDate, $checkOutTime, '12:00') : null;

        // Statuses that block a room regardless of which dates are being
        // browsed: they need staff action (cleaning/maintenance) that isn't
        // tied to a specific booking's checkout date. 'In-House'/'Checkin'
        // are deliberately excluded here -- those clear themselves once the
        // occupying booking's dates (and actual times) are past, which is
        // determined below via a precise datetime overlap check rather than
        // trusting the room's current snapshot status (that's what "Show All"
        // needs: it skips the date-overlap pre-filter in the service, so this
        // per-room check is the only thing guarding against double-booking).
        $hardBlockStatuses = ['dirty', 'Room Refresh', 'Checkout', 'Out of Order', 'maintenance'];

        return response()->json(
            $rooms->map(function ($room) use ($guest, $checkInDate, $checkOutDate, $newCheckInDT, $newCheckOutDT, $stayType, $hardBlockStatuses) {
                // Room base prices from master room
                $pricePublic =
                    (float) ($room->price_public ??
                        $room->roomType->base_price);
                $priceSales =
                    (float) ($room->price_sales ??
                        ($room->price_public ?? $room->roomType->base_price));
                $priceKos =
                    (float) ($room->price_kos ?? ($room->price_public ?? 0));

                $isHardBlocked = in_array($room->status, $hardBlockStatuses);

                // Current/overlapping booking for the requested window (if any)
                $currentBooking = null;
                if (!$isHardBlocked && $newCheckOutDT) {
                    $candidates = $room->bookings()
                        ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
                        ->whereDate('check_in', '<=', $checkOutDate->copy()->addDay())
                        ->whereDate('check_out', '>=', $checkInDate->copy()->subDay())
                        ->with('guest:id,name')
                        ->latest('check_in')
                        ->get();
                    $currentBooking = $candidates->first(function ($b) use ($newCheckInDT, $newCheckOutDT) {
                        $existingIn = $this->bookingService->combineDateAndTime($b->check_in, $b->check_in_time, '14:00');
                        $existingOut = $this->bookingService->combineDateAndTime($b->check_out, $b->check_out_time, '12:00');
                        return $existingIn->lt($newCheckOutDT) && $existingOut->gt($newCheckInDT);
                    });
                } elseif (!$isHardBlocked && !$newCheckOutDT && !in_array($room->status, ['Available', 'available', 'Clean', 'clean'])) {
                    // No specific dates requested (room-list-only call) -- fall back
                    // to whatever booking is currently active for display purposes.
                    $currentBooking = $room->bookings()
                        ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
                        ->with('guest:id,name')
                        ->latest('check_in')
                        ->first();
                }

                $isAvailable = !$isHardBlocked && !$currentBooking;

                // Current automatic pricing logic for reference/default
                $pricingResult = $this->pricingService->calculateRoomPrice(
                    $room->roomType,
                    $guest,
                    $checkInDate,
                );
                $effectivePrice = $pricingResult['price'];
                $effectiveTier = $pricingResult['tier'];
                if ($effectivePrice == (float) $room->roomType->base_price) {
                    $effectivePrice = $pricePublic;
                }

                return [
                    "id" => $room->id,
                    "room_number" => $room->room_number,
                    "room_type" => $room->roomType->name ?? "N/A",
                    "status" => $room->status,
                    "available" => $isAvailable,
                    "price_public" => $pricePublic,
                    "price_sales" => $priceSales,
                    "price_high_season" =>
                        (float) ($room->price_high_season ?? $pricePublic),
                    "price_kos" => $priceKos,
                    "yearly_price" =>
                        (float) ($room->yearly_price ??
                            ($room->price_kos ?? 0)),
                    "price_default" => $effectivePrice,
                    "price_default_tier" => $effectiveTier,
                    "price_breakfast_public" =>
                        (float) ($room->price_breakfast_public ?? 0),
                    "price_breakfast_sales" =>
                        (float) ($room->price_breakfast_sales ?? 0),
                    "price_breakfast_high_season" =>
                        (float) ($room->price_breakfast_high_season ?? 0),
                    "status" => $room->status,
                    "floor" => $room->floor ?? "-",
                    "price_extra_person" =>
                        (float) ($room->price_extra_person ?? 0),
                    "max_occupancy" => (int) ($room->max_occupancy ?? 2),
                    "current_booking" => $currentBooking ? [
                        "id" => $currentBooking->id,
                        "guest_name" => $currentBooking->guest->name ?? null,
                    ] : null,
                ];
            }),
        );
    }

    /**
     * Get kost pricing for a room + duration (AJAX).
     */
    public function getKostPrice(Request $request)
    {
        $room = Room::with("kostPricingTiers")->findOrFail($request->room_id);
        $duration = (int) $request->duration_months;

        $tier = $room
            ->kostPricingTiers()
            ->where("duration_months", $duration)
            ->first();

        $basePrice = (float) ($room->price_kos ?? ($room->price_public ?? 0));

        if ($tier) {
            $monthlyRate = $tier->getEffectiveRate($basePrice);
            $hasDiscount = true;
        } else {
            $monthlyRate = $basePrice;
            $hasDiscount = false;
        }

        return response()->json([
            "monthly_rate" => round($monthlyRate, 2),
            "total" => round($monthlyRate * $duration, 2),
            "original_rate" => round($basePrice, 2),
            "savings" => round(($basePrice - $monthlyRate) * $duration, 2),
            "has_discount" => $hasDiscount,
        ]);
    }

    /**
     * Store a newly created booking in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            "guest_id" => "required|exists:guests,id",
            "room_id" => "required|exists:rooms,id",
            "check_in" =>
                "required|date" .
                (auth()->user()->can("manage reservations") ||
                auth()->user()->can("bookings.create")
                    ? ""
                    : "|after_or_equal:today"),
            "check_out" =>
                $request->check_in_time &&
                $request->check_in_time >= "00:00" &&
                $request->check_in_time < "06:00"
                    ? "required|date|after_or_equal:check_in"
                    : "required|date|after:check_in",
            "check_in_time" => "nullable|string|max:5",
            "check_out_time" => "nullable|string|max:5",
            "adults" => "required|integer|min:1",
            "children" => "nullable|integer|min:0",
            "voucher_code" => "nullable|string|exists:vouchers,code",
            "notes" => "nullable|string",
            "special_requests" => "nullable|string",
            "stay_type" => "required|in:daily,monthly,yearly",
            "manual_price" => "nullable|numeric|min:0",
            "deposit_amount" => "nullable|numeric|min:0",
            "down_payment" => "nullable|numeric|min:0",
            "bank_account_id" => "nullable|exists:bank_accounts,id",
            "exclude_tax" => "nullable|boolean",
            "source" => "nullable|string",
            "booking_source_id" => "nullable|exists:booking_sources,id",
            "include_breakfast" => "nullable|boolean",
            "tier_applied" => "nullable|string|in:public,sales,high_season",
        ]);

        if ($request->filled("special_requests")) {
            $validated["notes"] =
                $request->special_requests .
                ($request->filled("notes") ? "\n" . $request->notes : "");
        }

        $validated["include_breakfast"] = $request->has("include_breakfast");

        try {
            // Map the form's exclude_tax checkbox to the service's expectations
            if ($request->has("exclude_tax")) {
                $validated["exclude_tax"] = true;
            } else {
                $validated["exclude_tax"] = false;
            }

            $booking = $this->bookingService->createBooking($validated);
            return $this->ajaxOrRedirect(
                "Booking created successfully.",
                route("bookings.show", $booking->id),
                $booking,
                201,
            );
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return back()->with("error", $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Booking $booking)
    {
        $booking->load([
            "guest.emergencyContacts",
            "room.roomType",
            "transactions.bankAccount",
            "posOrders.items",
            "roomTransfers.fromRoom",
            "roomTransfers.toRoom",
            "roomTransfers.transferredBy",
        ]);

        $bankAccounts = \App\Models\BankAccount::where(
            "hotel_id",
            active_hotel_id(),
        )
            ->where("is_active", true)
            ->get();
        // Only rental/persewaan items here (don't reduce stock) — regular
        // stock-tracked inventory is added via POS front office instead.
        $inventoryItems = \App\Models\Inventory::where(
            "hotel_id",
            active_hotel_id(),
        )
            ->where("is_active", true)
            ->where("price_per_unit", ">", 0)
            ->whereHas("inventoryCategory", fn ($q) => $q->where("name", "SEWA"))
            ->orderBy("name")
            ->get();

        $manualExtraTotal = \App\Models\Transaction::where('booking_id', $booking->id)
            ->where("type", "charge")
            ->where("status", "success")
            ->where("reference_id", null)
            ->where("is_deposit", false)
            ->sum("amount");
        $posTotal = \App\Models\PosOrder::where('booking_id', $booking->id)
            ->where("status", "completed")
            ->sum('total_amount');

        $extraCharges = $booking->transactions
            ->where("type", "charge")
            ->where("status", "success")
            ->where("reference_id", null)
            ->where("is_deposit", false);
        $posOrders = $booking->posOrders;

        // Deposit items
        $depositCharges = $booking->transactions
            ->where("type", "charge")
            ->where("is_deposit", true);
        $depositRefunds = $booking->transactions
            ->where("type", "refund")
            ->where("is_deposit", true);
        $totalDeposit = $depositCharges->sum("amount");
        $totalRefunded = $depositRefunds->sum("amount");
        $depositOutstanding = $totalDeposit - $totalRefunded;

        // total_price already includes deposit_amount (invariant maintained by
        // createBooking, update, checkIn, editDeposit, deleteDeposit) -- adding
        // $totalDeposit here again double-counted the deposit in Grand Total
        // and made staff over-collect from the guest.
        $grandTotal =
            $booking->total_price +
            $manualExtraTotal +
            $posTotal +
            $totalDeposit;
        $totalPayments = \App\Models\Transaction::where('booking_id', $booking->id)
            ->where("type", "payment")
            ->where("status", "success")
            ->sum("amount");
        $remainingBalance = max(0, round($grandTotal - $totalPayments, 2));

        return view(
            "bookings.show",
            compact(
                "booking",
                "bankAccounts",
                "inventoryItems",
                "remainingBalance",
                "extraCharges",
                "posOrders",
                "grandTotal",
                "depositCharges",
                "depositRefunds",
                "totalDeposit",
                "totalRefunded",
                "depositOutstanding",
            ),
        );
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Booking $booking)
    {
        $this->authorize("bookings.edit");

        if (
            in_array($booking->status, ["checked_out", "cancelled", "no_show"])
        ) {
            $msg = "Cannot add charges to a " . $booking->status . " booking.";
            if ($this->isAjaxRequest()) return $this->ajaxError($msg);
            return back()->with("error", $msg);
        }

        $booking->load(["guest", "room.roomType"]);
        $roomTypes = \App\Models\RoomType::where("is_active", true)->get();
        $rooms = collect();
        $taxPercentage =
            \App\Models\Hotel::find(active_hotel_id())->tax_percentage ?? 0;

        return view(
            "bookings.edit",
            compact("booking", "rooms", "roomTypes", "taxPercentage"),
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Booking $booking)
    {
        $this->authorize("bookings.edit");

        if (
            in_array($booking->status, ["checked_out", "cancelled", "no_show"])
        ) {
            return back()->with(
                "error",
                "Cannot update a " . $booking->status . " booking.",
            );
        }

        $validated = $request->validate([
            "guest_id" => "required|exists:guests,id",
            "room_id" => "required|exists:rooms,id",
            "check_in" =>
                "required|date" .
                (auth()->user()->can("manage reservations") ||
                auth()->user()->can("bookings.create")
                    ? ""
                    : "|after_or_equal:today"),
            "check_out" =>
                $request->check_in_time &&
                $request->check_in_time >= "00:00" &&
                $request->check_in_time < "06:00"
                    ? "required|date|after_or_equal:check_in"
                    : "required|date|after:check_in",
            "check_in_time" => "nullable|string|max:5",
            "check_out_time" => "nullable|string|max:5",
            "adults" => "required|integer|min:1",
            "children" => "nullable|integer|min:0",
            "voucher_code" => "nullable|string|exists:vouchers,code",
            "special_requests" => "nullable|string",
            "stay_type" => "required|in:daily,monthly,yearly",
            "manual_price" => "nullable|numeric|min:0",
            "deposit_amount" => "nullable|numeric|min:0",
            "status" =>
                "required|string|in:pending,confirmed,checked_in,checked_out,cancelled,no_show",
            "payment_status" =>
                "required|string|in:unpaid,partial,paid,refunded",
            "source" => "nullable|in:online,walk_in,phone,email,agent",
            "booking_source_id" => "nullable|exists:booking_sources,id",
            "include_breakfast" => "nullable|boolean",
            "tier_applied" => "nullable|string|in:public,sales,high_season",
        ]);

        try {
            DB::transaction(function () use ($validated, $booking) {
                $oldStatus = $booking->status;
                $oldRoom = $booking->room;
                $newRoom = Room::with("roomType")->findOrFail(
                    $validated["room_id"],
                );

                $availableRooms = $this->bookingService->getAvailableRooms(
                    $validated["check_in"],
                    $validated["check_out"],
                    $newRoom->room_type_id,
                    $validated["stay_type"],
                    false,
                    $validated["check_in_time"] ?? null,
                    $validated["check_out_time"] ?? null,
                );

                if (
                    $newRoom->id !== $booking->room_id &&
                    !$availableRooms->contains("id", $newRoom->id)
                ) {
                    throw new \Exception(
                        "Room is not available for the selected dates.",
                    );
                }

                $priceData = $this->bookingService->calculatePrice(
                    $newRoom,
                    $validated["check_in"],
                    $validated["check_out"],
                    $validated["voucher_code"] ?? null,
                    false,
                    $validated["stay_type"],
                    $validated["manual_price"] ?? null,
                    $validated["check_in_time"] ?? null,
                );

                $notes = $validated["special_requests"] ?? null;
                $depositAmount = $validated["deposit_amount"] ?? 0;

                // Breakfast Calculation for update
                $includeBreakfast = $validated["include_breakfast"] ?? false;
                $breakfastTotal = 0;
                $appliedTier = "public"; // Default tier
                if ($includeBreakfast) {
                    $pax = 1; // Fixed 1 pack per room per night
                    $nights = max(
                        1,
                        Carbon::parse($validated["check_in"])->diffInDays(
                            Carbon::parse($validated["check_out"]),
                        ),
                    );

                    // Use frontend-sent tier, fall back to auto-detected, then default 'public'
                    if (
                        !empty($validated["tier_applied"]) &&
                        in_array($validated["tier_applied"], [
                            "public",
                            "sales",
                            "high_season",
                        ])
                    ) {
                        $appliedTier = $validated["tier_applied"];
                    } else {
                        $appliedTier =
                            $priceData["breakdown"]["tier_applied"] ?? "public";
                    }
                    $breakfastPrice = 0;
                    if ($appliedTier === "high_season") {
                        $breakfastPrice =
                            (float) ($newRoom->price_breakfast_high_season ??
                                0);
                    } elseif ($appliedTier === "sales") {
                        $breakfastPrice =
                            (float) ($newRoom->price_breakfast_sales ?? 0);
                    } else {
                        $breakfastPrice =
                            (float) ($newRoom->price_breakfast_public ?? 0);
                    }
                    $pax = ($validated['adults'] ?? 1) + ($validated['children'] ?? 0);
                    $breakfastTotal = $breakfastPrice * $pax * $nights;
                }

                // Store tier_applied in pricing_breakdown
                $priceData["breakdown"]["tier_applied"] = $appliedTier;
                $priceData["breakdown"]["breakfast_total"] = round(
                    $breakfastTotal,
                    2,
                );

                $totalPrice =
                    $priceData["total_price"] +
                    $breakfastTotal;

                $booking->update([
                    "guest_id" => $validated["guest_id"],
                    "room_id" => $newRoom->id,
                    "check_in" => $validated["check_in"],
                    "check_out" => $validated["check_out"],
                    "check_in_time" =>
                        $validated["check_in_time"] ?? $booking->check_in_time,
                    "check_out_time" => $validated["check_out_time"] ?? "12:00",
                    "adults" => $validated["adults"],
                    "children" => $validated["children"] ?? 0,
                    "base_price" => $priceData["base_price"],
                    "discount_amount" => $priceData["discount_amount"],
                    "tax_amount" => $priceData["tax_amount"],
                    "total_price" => $totalPrice,
                    "pricing_breakdown" => $priceData["breakdown"],
                    "stay_type" => $validated["stay_type"],
                    "deposit_amount" => $depositAmount,
                    "status" => $validated["status"],
                    "payment_status" => $validated["payment_status"],
                    "notes" => $notes,
                    "voucher_code" => $validated["voucher_code"] ?? null,
                    "source" => $validated["source"] ?? $booking->source,
                    "booking_source_id" =>
                        $validated["booking_source_id"] ?? null,
                    "include_breakfast" => $includeBreakfast,
                ]);

                Transaction::where("booking_id", $booking->id)
                    ->where("reference_id", "BOOK-" . $booking->id)
                    ->update([
                        "guest_id" => $validated["guest_id"],
                        "amount" => $totalPrice,
                        "description" =>
                            "Booking charge for room " .
                            $newRoom->room_number .
                            " (including deposit)",
                    ]);

                if (
                    $oldRoom &&
                    $oldRoom->id !== $newRoom->id &&
                    in_array($oldRoom->status, ["In-House", "occupied"])
                ) {
                    $oldRoom->update(["status" => "Checkout"]);
                }

                if ($validated["status"] === "checked_in") {
                    $newRoom->update(["status" => "In-House"]);
                } elseif ($validated["status"] === "checked_out") {
                    $newRoom->update(["status" => "Checkout"]);
                } elseif (
                    in_array($validated["status"], ["cancelled", "no_show"])
                ) {
                    if (in_array($newRoom->status, ["In-House", "occupied"])) {
                        $newRoom->update(["status" => "dirty"]);
                    }
                } elseif (
                    $oldStatus !== $validated["status"] &&
                    in_array($newRoom->status, ["In-House", "occupied"])
                ) {
                    $newRoom->update(["status" => "Checkout"]);
                }

                \App\Models\AuditLog::log(
                    'booking.updated',
                    "Booking #{$booking->id} details updated manually",
                    $booking
                );
            });

            return $this->ajaxOrRedirect(
                "Booking updated successfully.",
                route("bookings.show", $booking->id),
                $booking,
            );
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return back()
                ->with("error", "Failed to update booking: " . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Add an extra charge item.
     */
    public function addItemCharge(Request $request, Booking $booking)
    {
        if (
            in_array($booking->status, ["checked_out", "cancelled", "no_show"])
        ) {
            return back()->with(
                "error",
                "Cannot add charges to a " . $booking->status . " booking.",
            );
        }

        $validated = $request->validate([
            "inventory_id" => "required|exists:inventories,id",
            "quantity" => "required|integer|min:1",
            "deposit_paid" => "nullable|in:0,1",
            "is_deposit" => "nullable|in:0,1",
        ]);

        try {
            DB::transaction(function () use ($validated, $booking) {
                $item = \App\Models\Inventory::find($validated["inventory_id"]);

                if (!$item) {
                    throw new \Exception("Inventory item not found.");
                }

                $isRentalItem = strtoupper($item->inventoryCategory?->name ?? "") === "SEWA";

                if (!$isRentalItem && $item->stock < $validated["quantity"]) {
                    throw new \Exception("Stok {$item->name} tidak cukup. Sisa stok: {$item->stock}.");
                }

                $addPosItem = function () use ($booking, $item, $validated) {
                    $posOrder = \App\Models\PosOrder::where(
                        "booking_id",
                        $booking->id,
                    )
                        ->where("payment_status", "unpaid")
                        ->where("status", "!=", "cancelled")
                        ->first();

                    if (!$posOrder) {
                        $posOrder = \App\Models\PosOrder::create([
                            "hotel_id" => active_hotel_id(),
                            "booking_id" => $booking->id,
                            "guest_id" => $booking->guest_id,
                            "user_id" => auth()->id(),
                            "order_number" =>
                                "POS-" .
                                date("Ymd") .
                                "-" .
                                strtoupper(\Illuminate\Support\Str::random(4)),
                            "subtotal" => 0,
                            "total_amount" => 0,
                            "payment_status" => "unpaid",
                            "status" => "completed",
                            "notes" => "Room service charges",
                        ]);
                    }

                    \App\Models\PosOrderItem::create([
                        "hotel_id" => active_hotel_id(),
                        "pos_order_id" => $posOrder->id,
                        "inventory_id" => $item->id,
                        "item_name" => $item->name,
                        "quantity" => $validated["quantity"],
                        "price_per_unit" => $item->price_per_unit,
                        "subtotal" =>
                            $item->price_per_unit * $validated["quantity"],
                    ]);

                    $newSubtotal = $posOrder->items()->sum("subtotal");
                    $taxAmount = 0;

                    $posOrder->update([
                        "subtotal" => $newSubtotal,
                        "tax_amount" => $taxAmount,
                        "total_amount" => $newSubtotal + $taxAmount,
                    ]);
                };

                if ($item->is_refundable) {
                    $depositAmount =
                        (float) $item->deposit_amount > 0
                            ? $item->deposit_amount
                            : $item->price_per_unit;
                    $totalDeposit = $depositAmount * $validated["quantity"];

                    $booking->transactions()->create([
                        "hotel_id" => active_hotel_id(),
                        "guest_id" => $booking->guest_id,
                        "user_id" => auth()->id(),
                        "type" => "charge",
                        "amount" => $totalDeposit,
                        "description" =>
                            "Deposit: " .
                            $item->name .
                            " (x" .
                            $validated["quantity"] .
                            ")",
                        "status" => "success",
                        "is_deposit" => true,
                    ]);

                    if ($item->price_per_unit > 0) {
                        $addPosItem();
                    }
                } else {
                    $addPosItem();
                }

                if (!$isRentalItem) {
                    $item->decrement("stock", $validated["quantity"]);
                }

                if ($booking->payment_status === "paid") {
                    $booking->update(["payment_status" => "partial"]);
                }
            });

            return $this->ajaxOrRedirect(
                "Item added to order successfully.",
                route("bookings.show", $booking->id),
            );
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return back()->with("error", $e->getMessage());
        }
    }

    /**
     * Add a custom charge (manual) with tax support.
     */
    public function addCustomCharge(Request $request, Booking $booking)
    {
        if (
            in_array($booking->status, ["checked_out", "cancelled", "no_show"])
        ) {
            return back()->with(
                "error",
                "Cannot add charges to a " . $booking->status . " booking.",
            );
        }

        $validated = $request->validate([
            "description" => "required|string|max:255",
            "amount" => "required|numeric|min:0",
            "tax_amount" => "nullable|numeric|min:0",
            "is_markup" => "nullable|boolean",
            "is_room_markup" => "nullable|boolean",
        ]);

        try {
            DB::transaction(function () use ($validated, $booking) {
                $taxAmount = $validated["tax_amount"] ?? 0;
                $baseAmount = $validated["amount"];
                $totalAmount = $baseAmount + $taxAmount;
                $isMarkup = $validated["is_markup"] ?? false;
                $isRoomMarkup = $validated["is_room_markup"] ?? false;

                // 1. Create the Charge (to inflate invoice)
                $charge = $booking->transactions()->create([
                    "hotel_id" => active_hotel_id(),
                    "guest_id" => $booking->guest_id,
                    "user_id" => auth()->id(),
                    "type" => "charge",
                    "amount" => $totalAmount,
                    "tax_amount" => $taxAmount,
                    "payment_method" => null,
                    "reference_id" => null,
                    "description" => $isMarkup
                        ? ($isRoomMarkup ? "[ROOM_MARKUP] " : "[MARKUP] ") .
                            $validated["description"]
                        : $validated["description"],
                    "status" => "success",
                    "is_markup" => $isMarkup,
                    "is_room_markup" => $isRoomMarkup,
                ]);

                // 2. If it's a Markup, automatically create a matching dummy payment
                // This keeps Remaining Balance at 0 and doesn't affect real cash flow
                if ($isMarkup) {
                    $booking->transactions()->create([
                        "hotel_id" => active_hotel_id(),
                        "guest_id" => $booking->guest_id,
                        "user_id" => auth()->id(),
                        "bank_account_id" => null, // No real bank account affected
                        "type" => "payment",
                        "amount" => $totalAmount,
                        "payment_method" => "cash",
                        "description" => "Markup Settlement (Auto-generated)",
                        "status" => "success",
                        "is_markup" => true, // Flagged so it can be excluded from real revenue reports
                    ]);
                }

                // Refresh booking to update payment status
                $grandTotal = BookingPriceService::grandTotal($booking);
                $totalPaid = $booking
                    ->transactions()
                    ->where("type", "payment")
                    ->where("status", "success")
                    ->sum("amount");

                if ($totalPaid >= $grandTotal) {
                    $booking->update(["payment_status" => "paid"]);
                }
            });

            return $this->ajaxOrRedirect(
                "Charge added successfully.",
                route("bookings.show", $booking->id),
            );
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return back()->with("error", $e->getMessage());
        }
    }

    /**
     * Delete a charge
     * Delete a manual custom charge.
     */
    public function deleteCharge(Booking $booking, Transaction $transaction)
    {
        $this->authorize("delete transactions");

        // Safety checks
        if ($transaction->booking_id !== $booking->id) {
            return back()->with("error", "Unauthorized action.");
        }

        if ($transaction->type !== "charge") {
            return back()->with(
                "error",
                "Only charge transactions can be deleted.",
            );
        }

        // Check if there are any payments made AFTER this charge was created
        $hasSubsequentPayment = $booking
            ->transactions()
            ->where("type", "payment")
            ->where("status", "success")
            ->where("created_at", ">=", $transaction->created_at)
            ->exists();

        if (
            $hasSubsequentPayment ||
            in_array($booking->status, ["checked_out", "cancelled", "no_show"])
        ) {
            return back()->with(
                "error",
                "Tidak dapat menghapus biaya: Biaya ini sudah terkunci oleh transaksi pembayaran atau booking sudah " .
                    $booking->status .
                    ".",
            );
        }

        try {
            $transaction->delete();
            return $this->ajaxOrRedirect(
                "Charge deleted successfully.",
                route("bookings.show", $booking->id),
            );
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return back()->with("error", $e->getMessage());
        }
    }

    /**
     * Refund a single deposit
     * Refund a specific deposit item.
     */
    public function refundDeposit(Booking $booking, Transaction $transaction)
    {
        if (
            $transaction->booking_id !== $booking->id ||
            !$transaction->is_deposit ||
            $transaction->type !== "charge"
        ) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError("Invalid deposit transaction.");
            }
            return back()->with("error", "Invalid deposit transaction.");
        }

        $alreadyRefunded = $booking
            ->transactions()
            ->where("type", "refund")
            ->where("is_deposit", true)
            ->where(
                "description",
                "LIKE",
                "Refund: " . $transaction->description,
            )
            ->exists();

        if ($alreadyRefunded) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError("Deposit ini sudah dikembalikan.");
            }
            return back()->with("error", "Deposit ini sudah dikembalikan.");
        }

        try {
            $refundAccount = $this->resolveDepositRefundAccount($booking);
            DB::transaction(function () use ($booking, $transaction, $refundAccount) {
                $booking->transactions()->create([
                    "hotel_id" => active_hotel_id(),
                    "guest_id" => $booking->guest_id,
                    "user_id" => auth()->id(),
                    "bank_account_id" => $refundAccount?->id,
                    "payment_method" => $refundAccount
                        ? (str_contains(strtolower($refundAccount->name), "tunai") ? "cash" : "bank_transfer")
                        : null,
                    "type" => "refund",
                    "amount" => $transaction->amount,
                    "description" => "Refund: " . $transaction->description,
                    "status" => "success",
                    "is_deposit" => true,
                ]);
                if ($refundAccount) {
                    $refundAccount->decrement("balance", $transaction->amount);
                    $refundAccount->decrement("available_balance", $transaction->amount);
                }
            });

            return $this->ajaxOrRedirect(
                "Deposit berhasil dikembalikan.",
                route("bookings.show", $booking->id),
            );
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return back()->with("error", $e->getMessage());
        }
    }

    /**
     * Refund all outstanding deposit items.
     */
    public function refundAllDeposits(Booking $booking)
    {
        try {
            $refundAccount = $this->resolveDepositRefundAccount($booking);
            DB::transaction(function () use ($booking, $refundAccount) {
                $outstandingDeposits = $booking
                    ->transactions()
                    ->where("type", "charge")
                    ->where("is_deposit", true)
                    ->get();

                $totalRefunded = 0;
                foreach ($outstandingDeposits as $deposit) {
                    $alreadyRefunded = $booking
                        ->transactions()
                        ->where("type", "refund")
                        ->where("is_deposit", true)
                        ->where(
                            "description",
                            "LIKE",
                            "Refund: " . $deposit->description,
                        )
                        ->exists();

                    if (!$alreadyRefunded) {
                        $booking->transactions()->create([
                            "hotel_id" => active_hotel_id(),
                            "guest_id" => $booking->guest_id,
                            "user_id" => auth()->id(),
                            "bank_account_id" => $refundAccount?->id,
                            "payment_method" => $refundAccount
                                ? (str_contains(strtolower($refundAccount->name), "tunai") ? "cash" : "bank_transfer")
                                : null,
                            "type" => "refund",
                            "amount" => $deposit->amount,
                            "description" => "Refund: " . $deposit->description,
                            "status" => "success",
                            "is_deposit" => true,
                        ]);
                        $totalRefunded += (float) $deposit->amount;
                    }
                }

                if ($refundAccount && $totalRefunded > 0) {
                    $refundAccount->decrement("balance", $totalRefunded);
                    $refundAccount->decrement("available_balance", $totalRefunded);
                }
            });

            return $this->ajaxOrRedirect(
                "Semua deposit berhasil dikembalikan.",
                route("bookings.show", $booking->id),
            );
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return back()->with("error", $e->getMessage());
        }
    }

    /**
     * Determine which bank account the deposit refund money leaves from,
     * so the refund shows in the right wallet column of the daily report
     * and the account balance stays accurate. Order: explicit request
     * param, then the account the guest's latest payment went into
     * (refund normally comes out of the same drawer), then the cash
     * (Tunai) account, then any account.
     */
    private function resolveDepositRefundAccount(Booking $booking): ?\App\Models\BankAccount
    {
        if (request("bank_account_id")) {
            $account = \App\Models\BankAccount::find(request("bank_account_id"));
            if ($account) {
                return $account;
            }
        }

        // Deposit (jaminan) refunds are handed back in cash by policy -> default to
        // the Tunai account regardless of how the guest originally paid. Falls back
        // to the general refund-account resolver if no cash account exists.
        $cash = \App\Models\BankAccount::withoutGlobalScopes()
            ->where("hotel_id", $booking->hotel_id)
            ->where("name", "LIKE", "%tunai%")
            ->first();

        return $cash ?? $this->bookingService->resolveRefundAccount($booking);
    }

    /**
     * Add a payment to the booking.
     */
    public function addPayment(Request $request, Booking $booking)
    {
        if (
            in_array($booking->status, ["checked_out", "cancelled", "no_show"])
        ) {
            return back()->with(
                "error",
                "Cannot add payment to a " . $booking->status . " booking.",
            );
        }

        $validated = $request->validate([
            "bank_account_id" => "required|exists:bank_accounts,id",
            "amount" => "required|numeric|min:0",
            "description" => "nullable|string|max:255",
            "payment_date" => "nullable|date",
        ]);

        $paymentDate = $request->filled("payment_date")
            ? \Carbon\Carbon::parse($request->payment_date)
            : now();

        $grandTotal = BookingPriceService::grandTotal($booking);

        try {
            DB::transaction(function () use (
                $validated,
                $booking,
                $grandTotal,
                $paymentDate,
            ) {
                $account = \App\Models\BankAccount::find(
                    $validated["bank_account_id"],
                );
                $paymentMethod = str_contains(
                    strtolower($account->name),
                    "tunai",
                )
                    ? "cash"
                    : "bank_transfer";

                $booking->transactions()->create([
                    "hotel_id" => active_hotel_id(),
                    "guest_id" => $booking->guest_id,
                    "user_id" => auth()->id(),
                    "bank_account_id" => $account->id,
                    "type" => "payment",
                    "amount" => $validated["amount"],
                    "payment_method" => $paymentMethod,
                    "description" =>
                        $validated["description"] ?? "Booking payment",
                    "status" => "success",
                    "is_realized" => false, // Realized at checkout
                    "created_at" => $paymentDate,
                    "updated_at" => $paymentDate,
                ]);

                $account->increment("balance", $validated["amount"]);

                $totalPaid = $booking
                    ->transactions()
                    ->where("type", "payment")
                    ->where("status", "success")
                    ->sum("amount");
                if ($totalPaid >= $grandTotal) {
                    $booking->update(["payment_status" => "paid"]);
                } elseif ($totalPaid > 0) {
                    $booking->update(["payment_status" => "partial"]);
                }

                \App\Models\AuditLog::log(
                    'booking.payment_added',
                    "Payment of Rp " . number_format($validated["amount"], 0, ',', '.') . " added to booking",
                    $booking
                );
            });

            return $this->ajaxOrRedirect(
                "Payment recorded successfully.",
                route("bookings.show", $booking->id),
            );
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return back()->with(
                "error",
                "Failed to process payment: " . $e->getMessage(),
            );
        }
    }

    public function editPayment(
        Request $request,
        Booking $booking,
        Transaction $transaction,
    ) {
        // Middleware already checks: bookings.payment.edit|bookings.payment.edit.unlimited|edit payments

        // Safety checks
        if (
            $transaction->booking_id !== $booking->id ||
            $transaction->type !== "payment"
        ) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError("Unauthorized or invalid transaction.");
            }
            return back()->with(
                "error",
                "Unauthorized or invalid transaction.",
            );
        }

        $validated = $request->validate([
            "bank_account_id" => "required|exists:bank_accounts,id",
            "amount" => "required|numeric|min:0",
            "description" => "nullable|string|max:255",
            "payment_date" => "nullable|date",
        ]);

        $paymentDate = $request->filled("payment_date")
            ? \Carbon\Carbon::parse($request->payment_date)
            : $transaction->created_at;

        try {
            DB::transaction(function () use (
                $validated,
                $booking,
                $transaction,
                $paymentDate,
            ) {
                $oldAccount = \App\Models\BankAccount::find(
                    $transaction->bank_account_id,
                );
                $newAccount = \App\Models\BankAccount::find(
                    $validated["bank_account_id"],
                );

                // 1. Revert old account balance
                if ($oldAccount) {
                    $oldAccount->decrement("balance", $transaction->amount);
                    if ($transaction->is_realized) {
                        $oldAccount->decrement(
                            "available_balance",
                            $transaction->amount,
                        );
                    }
                }

                // 2. Calculate new payment method
                $paymentMethod = str_contains(
                    strtolower($newAccount->name),
                    "tunai",
                )
                    ? "cash"
                    : "bank_transfer";

                // 3. Update transaction
                $transaction->update([
                    "bank_account_id" => $newAccount->id,
                    "amount" => $validated["amount"],
                    "payment_method" => $paymentMethod,
                    "description" =>
                        $validated["description"] ?? $transaction->description,
                    "created_at" => $paymentDate,
                    "updated_at" => now(),
                ]);

                // 4. Apply new account balance
                $newAccount->increment("balance", $validated["amount"]);
                if ($transaction->is_realized) {
                    $newAccount->increment(
                        "available_balance",
                        $validated["amount"],
                    );
                }

                // 5. Update booking payment status
                $grandTotal = BookingPriceService::grandTotal($booking);

                $totalPaid = $booking
                    ->transactions()
                    ->where("type", "payment")
                    ->where("status", "success")
                    ->sum("amount");

                if ($totalPaid >= $grandTotal) {
                    $booking->update(["payment_status" => "paid"]);
                } elseif ($totalPaid > 0) {
                    $booking->update(["payment_status" => "partial"]);
                } else {
                    $booking->update(["payment_status" => "unpaid"]);
                }

                \App\Models\AuditLog::log(
                    'booking.payment_edited',
                    "Payment #{$transaction->id} edited to Rp " . number_format($validated["amount"], 0, ',', '.'),
                    $booking
                );
            });

            return $this->ajaxOrRedirect(
                "Payment updated successfully.",
                route("bookings.show", $booking->id),
            );
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return back()->with(
                "error",
                "Failed to update payment: " . $e->getMessage(),
            );
        }
    }

    public function deletePayment(Booking $booking, Transaction $transaction)
    {
        $this->authorize("delete transactions");

        // Safety checks
        if (
            $transaction->booking_id !== $booking->id ||
            $transaction->type !== "payment"
        ) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError("Unauthorized or invalid transaction.");
            }
            return back()->with(
                "error",
                "Unauthorized or invalid transaction.",
            );
        }

        if (
            in_array($booking->status, ["checked_out", "cancelled", "no_show"])
        ) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError(
                    "Tidak dapat menghapus pembayaran: Booking sudah " .
                        $booking->status .
                        ".",
                );
            }
            return back()->with(
                "error",
                "Tidak dapat menghapus pembayaran: Booking sudah " .
                    $booking->status .
                    ".",
            );
        }

        try {
            DB::transaction(function () use ($booking, $transaction) {
                // 1. Revert bank account balance
                $account = \App\Models\BankAccount::find(
                    $transaction->bank_account_id,
                );
                if ($account) {
                    $account->decrement("balance", $transaction->amount);
                    if ($transaction->is_realized) {
                        $account->decrement(
                            "available_balance",
                            $transaction->amount,
                        );
                    }
                }

                // 2. Delete the transaction
                $transaction->delete();

                // 3. Update booking payment status
                $grandTotal = BookingPriceService::grandTotal($booking);

                $totalPaid = $booking
                    ->transactions()
                    ->where("type", "payment")
                    ->where("status", "success")
                    ->sum("amount");

                if ($totalPaid >= $grandTotal) {
                    $booking->update(["payment_status" => "paid"]);
                } elseif ($totalPaid > 0) {
                    $booking->update(["payment_status" => "partial"]);
                } else {
                    $booking->update(["payment_status" => "unpaid"]);
                }

                \App\Models\AuditLog::log(
                    'booking.payment_deleted',
                    "Payment #{$transaction->id} of Rp " . number_format($transaction->amount, 0, ',', '.') . " deleted",
                    $booking
                );
            });

            return $this->ajaxOrRedirect(
                "Payment deleted successfully.",
                route("bookings.show", $booking->id),
            );
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return back()->with(
                "error",
                "Failed to delete payment: " . $e->getMessage(),
            );
        }
    }

    public function checkIn(Request $request, Booking $booking)
    {
        try {
            $addDeposit = $request->input('with_deposit') && !in_array($booking->stay_type, ['monthly', 'yearly']);
            $forceEarly = $request->user()->can('bookings.checkin.force');
            $depositAmount = $booking->hotel->room_deposit_amount ?? 0;
            $this->bookingService->checkIn($booking, $addDeposit ? $depositAmount : 0, $forceEarly);
            return $this->ajaxOrRedirect(
                "Guest checked in successfully.",
                route("bookings.show", $booking->id),
            );
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return back()->with("error", $e->getMessage());
        }
    }

    public function checkOut(Booking $booking)
    {
        try {
            $this->bookingService->checkOut($booking);
            return $this->ajaxOrRedirect(
                "Guest checked out successfully.",
                route("bookings.show", $booking->id),
            );
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return back()->with("error", $e->getMessage());
        }
    }

    public function cancel(Request $request, Booking $booking)
    {
        $request->validate([
            "cancelled_reason" => "required|string|max:500",
            "cancelled_photo" => "nullable|image|max:5120",
        ]);

        try {
            $this->bookingService->cancelBooking(
                $booking,
                $request->cancelled_reason,
                $request->file("cancelled_photo"),
            );
            return $this->ajaxOrRedirect(
                "Booking cancelled successfully.",
                route("bookings.show", $booking->id),
            );
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return back()->with("error", $e->getMessage());
        }
    }

    public function applyDiscount(Request $request, Booking $booking)
    {
        if (
            in_array($booking->status, ["checked_out", "cancelled", "no_show"])
        ) {
            $msg = "Cannot apply discount to a " . $booking->status . " booking.";
            if ($this->isAjaxRequest()) return $this->ajaxError($msg);
            return back()->with("error", $msg);
        }

        $validated = $request->validate([
            "voucher_code" => "nullable|string|exists:vouchers,code",
            "discount_amount" => "nullable|numeric|min:0",
            "reason" => "nullable|string|max:255",
        ]);

        try {
            $discountAmount = $validated["discount_amount"] ?? 0;
            $voucherCode = $validated["voucher_code"] ?? null;

            if ($voucherCode) {
                $voucher = \App\Models\Voucher::where("code", $voucherCode)->first();
                if (!$voucher || !$voucher->isValid()) {
                    $msg = "This voucher is invalid or has expired.";
                    if ($this->isAjaxRequest()) return $this->ajaxError($msg);
                    return back()->with("error", $msg);
                }
                if ($booking->base_price < $voucher->min_booking_amount) {
                    $msg = "Booking amount does not meet the minimum requirement.";
                    if ($this->isAjaxRequest()) return $this->ajaxError($msg);
                    return back()->with("error", $msg);
                }

                if ($voucher->type === "percentage") {
                    $voucherDiscount = ($booking->base_price * $voucher->value) / 100;
                    if ($voucher->max_discount > 0 && $voucherDiscount > $voucher->max_discount) {
                        $voucherDiscount = $voucher->max_discount;
                    }
                } else {
                    $voucherDiscount = $voucher->value;
                }

                $discountAmount = $voucherDiscount;
                $voucher->increment("usage_count");
            }

            $taxAmount = 0;
            $breakfastTotal = (is_array($booking->pricing_breakdown) ? ($booking->pricing_breakdown["breakfast_total"] ?? 0) : 0);
            $newTotal =
                $booking->base_price -
                $discountAmount +
                $taxAmount +
                ($booking->deposit_amount ?? 0) +
                $breakfastTotal;

            $notes = ($booking->notes ?? '') . " (Discount Applied: " . ($validated["reason"] ?? "Applied via details page") . ")";

            $totalPaid = \App\Models\Transaction::where('booking_id', $booking->id)
                ->where("type", "payment")
                ->where("status", "success")
                ->sum("amount");
            $newPaymentStatus = $totalPaid >= $newTotal ? 'paid' : ($totalPaid > 0 ? 'partial' : 'unpaid');

            $booking->update([
                "discount_amount" => $discountAmount,
                "voucher_code" => $voucherCode,
                "total_price" => $newTotal,
                "notes" => $notes,
                "payment_status" => $newPaymentStatus,
            ]);

            \App\Models\Transaction::where("booking_id", $booking->id)
                ->where("reference_id", "BOOK-" . $booking->id)
                ->update([
                    "amount" => $newTotal,
                    "description" =>
                        "Booking charge for room " .
                        ($booking->room->room_number ?? "N/A") .
                        ($booking->include_breakfast ? " (including breakfast)" : "") .
                        ($booking->deposit_amount > 0 ? " (including deposit)" : ""),
                ]);

            $msg = "Discount/Voucher applied successfully.";
            return $this->ajaxOrRedirect($msg, route("bookings.show", $booking->id));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            return back()->with("error", $e->getMessage());
        }
    }

    public function auditPricing(Booking $booking)
    {
        $this->authorize("bookings.edit");

        $auditService = new \App\Services\BookingAuditService();
        $result = $auditService->audit($booking);

        return $this->ajaxSuccess("Audit complete", $result);
    }

    public function applyAuditFix(Booking $booking)
    {
        $this->authorize("bookings.edit");

        try {
            $auditService = new \App\Services\BookingAuditService();
            $audit = $auditService->audit($booking);

            if (!$audit['has_anomaly']) {
                return $this->ajaxError("No anomaly found. Pricing is correct.");
            }

            $booking = $auditService->applyFix($booking);

            return $this->ajaxOrRedirect(
                "Pricing fixed successfully.",
                route("bookings.show", $booking->id)
            );
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            return back()->with("error", $e->getMessage());
        }
    }

    public function auditAllBookings()
    {
        $this->authorize("bookings.list");

        $bookings = Booking::with(['room.roomType', 'room.kostPricingTiers', 'transactions'])
            ->whereIn('status', ['confirmed', 'checked_in', 'pending'])
            ->where('is_custom', false)
            ->get();

        $auditService = new \App\Services\BookingAuditService();
        $results = [];
        $criticalCount = 0;

        foreach ($bookings as $booking) {
            $audit = $auditService->audit($booking);
            if ($audit['has_anomaly']) {
                $results[] = $audit;
                if ($audit['has_critical']) $criticalCount++;
            }
        }

        return $this->ajaxSuccess("Scan complete", [
            'total_scanned' => $bookings->count(),
            'anomaly_count' => count($results),
            'critical_count' => $criticalCount,
            'bookings' => $results,
        ]);
    }

    public function anomalyCatalog()
    {
        return $this->ajaxSuccess("Anomaly catalog", \App\Services\BookingAuditService::anomalyCatalog());
    }

    public function editDeposit(Request $request, Booking $booking)
    {
        if (
            in_array($booking->status, ["checked_out", "cancelled", "no_show"])
        ) {
            return back()->with(
                "error",
                "Cannot edit deposit on a " . $booking->status . " booking.",
            );
        }

        $validated = $request->validate([
            "deposit_amount" => "required|numeric|min:0",
        ]);

        try {
            DB::transaction(function () use ($validated, $booking) {
                $oldDeposit = $booking->deposit_amount ?? 0;
                $newDeposit = $validated["deposit_amount"];

                $booking->update([
                    "deposit_amount" => $newDeposit,
                    "total_price" =>
                        $booking->total_price - $oldDeposit + $newDeposit,
                ]);
            });

            return $this->ajaxOrRedirect(
                "Deposit updated successfully.",
                route("bookings.show", $booking->id),
            );
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return back()->with("error", $e->getMessage());
        }
    }

    public function deleteDeposit(Booking $booking)
    {
        if (
            in_array($booking->status, ["checked_out", "cancelled", "no_show"])
        ) {
            return back()->with(
                "error",
                "Cannot delete deposit on a " . $booking->status . " booking.",
            );
        }

        try {
            DB::transaction(function () use ($booking) {
                $deposit = $booking->deposit_amount ?? 0;
                $booking->update([
                    "deposit_amount" => 0,
                    "total_price" => $booking->total_price - $deposit,
                ]);
            });

            return $this->ajaxOrRedirect(
                "Deposit deleted successfully.",
                route("bookings.show", $booking->id),
            );
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return back()->with("error", $e->getMessage());
        }
    }

    public function invoice(Request $request, Booking $booking)
    {
        $booking->load([
            "guest",
            "room.roomType",
            "transactions.bankAccount",
            "hotel",
            "posOrders.items",
        ]);

        $showRoom = $request->query("room", "1") === "1";
        $showExtra = $request->query("extra", "1") === "1";
        $showPos = $request->query("pos", "1") === "1";

        // Get Room Markups
        $roomMarkups = $booking->transactions
            ->where("type", "charge")
            ->where("status", "success")
            ->where("is_room_markup", true);
        $roomMarkupTotal = $roomMarkups->sum("amount");

        $extraCharges = $showExtra
            ? $booking->transactions
                ->where("type", "charge")
                ->where("status", "success")
                ->where("reference_id", null)
                ->where("is_room_markup", false)
                ->where("is_deposit", false)
            : collect();

        $posOrders = $showPos ? $booking->posOrders : collect();

        // --- CLEAN INVOICE LOGIC ---
        // 1. Get all payments (Real + Markup)
        $allPayments = $booking->transactions
            ->where("type", "payment")
            ->where("status", "success")
            ->sortBy("created_at");

        // 2. Separate real and markup payments
        $realPayments = $allPayments->where("is_markup", false);
        $markupPaymentsTotal = $allPayments
            ->where("is_markup", true)
            ->sum("amount");

        // 3. Merge markup amount into the FIRST real payment for display
        $displayPayments = collect();
        if ($realPayments->count() > 0) {
            foreach ($realPayments as $index => $p) {
                $newP = $p->replicate();
                $newP->id = $p->id;
                $newP->created_at = $p->created_at;

                // Add all markup total to the first real payment row
                if ($index === $realPayments->keys()->first()) {
                    $newP->amount = $p->amount + $markupPaymentsTotal;
                } else {
                    $newP->amount = $p->amount;
                }
                $displayPayments->push($newP);
            }
        } elseif ($markupPaymentsTotal > 0) {
            // If ONLY markup exists, show it as a clean cash payment
            $fakeP = new \App\Models\Transaction();
            $fakeP->amount = $markupPaymentsTotal;
            $fakeP->payment_method = "cash";
            $fakeP->created_at = $booking->created_at;
            $displayPayments->push($fakeP);
        }

        // 4. Calculate display totals based on view filters
        $baseTotal = $showRoom ? $booking->total_price + $roomMarkupTotal : 0;
        $totalCharges =
            $baseTotal +
            $extraCharges->sum("amount") +
            $posOrders->sum("total_amount") +
            $booking->transactions->where("type", "charge")->where("is_deposit", true)->sum("amount");

        // 5. Cap display payments to match shown charges (prevent showing overpaid)
        $payments = collect();
        $runningPaymentTotal = 0;

        foreach ($displayPayments as $p) {
            if ($runningPaymentTotal >= $totalCharges) {
                break;
            }

            $remainingToCover = $totalCharges - $runningPaymentTotal;
            if ($p->amount <= $remainingToCover) {
                $payments->push($p);
                $runningPaymentTotal += $p->amount;
            } else {
                $partialP = $p->replicate();
                $partialP->amount = $remainingToCover;
                $partialP->created_at = $p->created_at;
                $payments->push($partialP);
                $runningPaymentTotal += $remainingToCover;
            }
        }

        $totalPaid = $runningPaymentTotal;
        $balance = max(0, $totalCharges - $totalPaid);

        return view(
            "bookings.invoice",
            compact(
                "booking",
                "extraCharges",
                "posOrders",
                "payments",
                "totalCharges",
                "totalPaid",
                "balance",
                "showRoom",
                "showExtra",
                "showPos",
                "roomMarkupTotal",
            ),
        );
    }

    public function sendInvoiceToWA(Booking $booking)
    {
        if (!$booking->guest || !$booking->guest->phone) {
            return back()->with("error", "Guest phone number not found.");
        }

        $booking->load(["guest", "room.roomType", "posOrders.items", "hotel"]);

        $extraCharges = $booking->transactions
            ->where("type", "charge")
            ->where("status", "success")
            ->where("reference_id", null)
            ->where("is_deposit", false);
        $posOrders = $booking->posOrders;
        $totalCharges =
            $booking->total_price +
            $extraCharges->sum("amount") +
            $posOrders->sum("total_amount") +
            $booking->transactions->where("type", "charge")->where("is_deposit", true)->sum("amount");
        $totalPaid = $booking->transactions
            ->where("type", "payment")
            ->where("status", "success")
            ->sum("amount");
        $balance = $totalCharges - $totalPaid;

        $hotelName = $booking->hotel->name ?? "Our Homestay";
        $text = "*INVOICE - {$hotelName}*\n";
        $text .= "------------------------------------------\n";
        $text .= "No: #INV-" . date("Ymd") . "-{$booking->id}\n";
        $text .= "Date: " . date("d M Y") . "\n\n";
        $text .= "*Guest Info:*\nName: {$booking->guest->name}\nRoom: {$booking->room->room_number} ({$booking->room->roomType->name})\n";
        $text .= "Check-in: " . $booking->check_in->format("d M Y") . "\n";
        $text .= "Check-out: " . $booking->check_out->format("d M Y") . "\n\n";
        $text .= "*Billing Details:*\n";
        $nights = $booking->check_in->diffInDays($booking->check_out);
        $text .=
            "- Room ({$nights} nights): Rp " .
            number_format($booking->base_price, 0, ",", ".") .
            "\n";
        if ($booking->include_breakfast) {
            $text .= "- Breakfast (included): ✓\n";
        }

        foreach ($extraCharges as $charge) {
            $text .=
                "- {$charge->description}: Rp " .
                number_format($charge->amount, 0, ",", ".") .
                "\n";
        }
        foreach ($posOrders as $order) {
            foreach ($order->items as $item) {
                $text .=
                    "- {$item->item_name} (x{$item->quantity}): Rp " .
                    number_format($item->subtotal, 0, ",", ".") .
                    "\n";
            }
        }

        if ($booking->tax_amount > 0) {
            $text .=
                "- Tax: Rp " .
                number_format($booking->tax_amount, 0, ",", ".") .
                "\n";
        }
        if ($booking->discount_amount > 0) {
            $text .=
                "- Discount: -Rp " .
                number_format($booking->discount_amount, 0, ",", ".") .
                "\n";
        }

        $text .=
            "------------------------------------------\n*TOTAL: Rp " .
            number_format($totalCharges, 0, ",", ".") .
            "*\n";
        $text .= "Paid: Rp " . number_format($totalPaid, 0, ",", ".") . "\n";
        if ($balance > 0) {
            $text .=
                "*Remaining: Rp " .
                number_format($balance, 0, ",", ".") .
                "*\n";
        } else {
            $text .= "*STATUS: FULLY PAID*\n";
        }
        $text .=
            "------------------------------------------\nThank you for staying with us!";

        if ($this->waService->sendMessage($booking->guest->phone, $text)) {
            return back()->with(
                "success",
                "Invoice sent to WhatsApp successfully!",
            );
        } else {
            return back()->with("error", "Failed to send WhatsApp message.");
        }
    }

    public function calendar()
    {
        $bookings = Booking::with(["guest", "room.roomType"])
            ->whereIn("status", ["confirmed", "checked_in", "checked_out", "pending"])
            ->get();

        $today = date("Y-m-d");
        $rooms = Room::with([
            "roomType",
            "bookings" => function ($q) use ($today) {
                $q->whereIn("status", ["confirmed", "pending", "checked_in"])
                    ->whereDate("check_in", "<=", $today)
                    ->whereDate("check_out", ">", $today);
            },
        ])
            ->orderBy("room_number")
            ->get();

        $rooms->each(function ($room) {
            $room->is_reserved_today = $room->bookings->isNotEmpty();
        });

        $availableRooms = Room::whereIn("status", [
            "Available",
            "Checkout",
            "Room Refresh",
        ])
            ->with("roomType")
            ->get();
        return view(
            "bookings.calendar",
            compact("bookings", "rooms", "availableRooms"),
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Booking $booking)
    {
        $this->authorize("delete transactions");

        if (
            in_array($booking->status, ["checked_in", "cancelled", "no_show"])
        ) {
            return back()->with(
                "error",
                "Cannot delete a booking that is " . $booking->status . ".",
            );
        }

        try {
            DB::transaction(function () use ($booking) {
                // Delete related transactions
                $booking->transactions()->delete();

                // Delete related POS orders and items
                foreach ($booking->posOrders as $order) {
                    $order->items()->delete();
                    $order->delete();
                }

                $booking->delete();
            });

            return $this->ajaxOrRedirect(
                "Booking deleted successfully.",
                route("bookings.index"),
            );
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->ajaxError($e->getMessage());
            }
            return back()->with(
                "error",
                "Failed to delete booking: " . $e->getMessage(),
            );
        }
    }

    /**
     * Show extend booking form
     */
    public function extend(Request $request, Booking $booking)
    {
        if (
            in_array($booking->status, ["checked_out", "cancelled", "no_show"])
        ) {
            return back()->with(
                "error",
                "Booking sudah " .
                    $booking->status .
                    ". Silakan buat booking baru.",
            );
        }

        $currentCheckOut = $booking->check_out->format("Y-m-d");
        $minNewCheckOut = date(
            "Y-m-d",
            strtotime($currentCheckOut . " +1 day"),
        );

        $room = $booking->room;
        $roomType = $room?->roomType;

        // Check if there is an upcoming booking on this room
        $nextBooking = Booking::where("room_id", $booking->room_id)
            ->where("id", "!=", $booking->id)
            ->whereNotIn("status", ["cancelled", "no_show", "checked_out"])
            ->whereDate("check_in", ">=", $booking->check_out)
            ->orderBy("check_in", "asc")
            ->first();

        $maxNewCheckOut = $nextBooking ? $nextBooking->check_in->format("Y-m-d") : null;
        $isBlocked = $nextBooking && $nextBooking->check_in->toDateString() === $booking->check_out->toDateString();

        if ($booking->stay_type === "monthly") {
            $pricePerMonth = $roomType
                ? $this->pricingService->calculateMonthlyPrice(
                    $roomType,
                    $booking->check_in,
                    $booking->check_out,
                    $room,
                )
                : $booking->base_price;
            return view(
                "bookings.extend",
                compact(
                    "booking",
                    "room",
                    "roomType",
                    "minNewCheckOut",
                    "maxNewCheckOut",
                    "isBlocked",
                    "nextBooking",
                    "pricePerMonth",
                ),
            );
        } else {
            // For daily, use original booking's average nightly rate
            $totalNights = $booking->check_in->diffInDays($booking->check_out);
            $pricePerNight = $totalNights > 0
                ? $booking->base_price / $totalNights
                : $booking->base_price;
            return view(
                "bookings.extend",
                compact(
                    "booking",
                    "room",
                    "roomType",
                    "minNewCheckOut",
                    "maxNewCheckOut",
                    "isBlocked",
                    "nextBooking",
                    "pricePerNight",
                ),
            );
        }
    }

    /**
     * Process extend booking
     */
    public function processExtend(Request $request, Booking $booking)
    {
        if (
            in_array($booking->status, ["checked_out", "cancelled", "no_show"])
        ) {
            $msg = "Tidak dapat memperpanjang booking yang sudah " . $booking->status . ".";
            if ($this->isAjaxRequest()) return $this->ajaxError($msg);
            return back()->with("error", $msg);
        }

        $request->validate(
            [
                "new_check_out" =>
                    "required|date|after:" .
                    $booking->check_out->format("Y-m-d"),
            ],
            [
                "new_check_out.after" =>
                    "Tanggal check-out baru harus setelah check-out saat ini.",
            ],
        );

        try {
            $newCheckOut = \Carbon\Carbon::parse($request->new_check_out);
            $currentCheckOut = $booking->check_out;
            $additionalDays = $currentCheckOut->diffInDays($newCheckOut);

            $room = $booking->room;
            $roomType = $room?->roomType;

            if (!$roomType) {
                $msg = "Room type tidak ditemukan.";
                if ($this->isAjaxRequest()) return $this->ajaxError($msg);
                return back()->with("error", $msg);
            }

            // Check if there is an overlapping booking on the same room
            $conflictBooking = Booking::where("room_id", $booking->room_id)
                ->where("id", "!=", $booking->id)
                ->whereNotIn("status", ["cancelled", "no_show", "checked_out"])
                ->where(function ($q) use ($currentCheckOut, $newCheckOut) {
                    $q->whereDate("check_in", "<", $newCheckOut)
                      ->whereDate("check_out", ">", $currentCheckOut);
                })
                ->first();

            if ($conflictBooking) {
                $roomNumber = $room?->room_number ?? $booking->room_id;
                $conflictGuest = $conflictBooking->guest?->name ?? 'Tamu';
                $conflictDates = $conflictBooking->check_in->format('d/m/Y') . ' s/d ' . $conflictBooking->check_out->format('d/m/Y');
                $msg = "Kamar {$roomNumber} tidak dapat diperpanjang ke tanggal tersebut karena sudah ada reservasi lain (#{$conflictBooking->id} - {$conflictGuest} pada {$conflictDates}). Silakan gunakan fitur Pindah Kamar (Room Transfer).";
                if ($this->isAjaxRequest()) return $this->ajaxError($msg);
                return back()->with("error", $msg)->withInput();
            }

            $additionalCost = 0;
            $newBreakdown = is_array($booking->pricing_breakdown)
                ? $booking->pricing_breakdown
                : [];

            if ($booking->stay_type === "monthly") {
                $totalNights = $booking->check_in->diffInDays($newCheckOut);
                $originalNights = $booking->check_in->diffInDays($currentCheckOut);
                $totalMonths = max(1, (int) round($totalNights / 30));
                $originalMonths = max(1, (int) round($originalNights / 30));
                $additionalMonths = max(1, $totalMonths - $originalMonths);

                $pricePerMonth = $this->pricingService->calculateMonthlyPrice(
                    $roomType,
                    $currentCheckOut,
                    $newCheckOut,
                    $room,
                );
                $additionalCost = $pricePerMonth * $additionalMonths;
                $extensionInfo = "{$additionalMonths} bulan";

                $dailyRate = $additionalDays > 0 ? $pricePerMonth / 30 : 0;
                for ($i = 0; $i < $additionalDays; $i++) {
                    $date = $currentCheckOut->copy()->addDays($i);
                    $newBreakdown[] = [
                        "date" => $date->toDateString(),
                        "day_of_week" => $date->format("l"),
                        "price" => round($dailyRate, 2),
                    ];
                }
            } else {
                $extensionInfo = "{$additionalDays} malam";
                // Use original booking's average nightly rate
                $originalNights = $booking->check_in->diffInDays($currentCheckOut);
                $nightPrice = $originalNights > 0
                    ? $booking->base_price / $originalNights
                    : $booking->base_price;
                for ($i = 0; $i < $additionalDays; $i++) {
                    $date = $currentCheckOut->copy()->addDays($i);
                    $additionalCost += $nightPrice;

                    $newBreakdown[] = [
                        "date" => $date->toDateString(),
                        "day_of_week" => $date->format("l"),
                        "price" => $nightPrice,
                    ];
                }
            }

            $periods = $newBreakdown["periods"] ?? [];
            if (empty($periods)) {
                $origFiltered = collect($newBreakdown)->filter(
                    fn($v, $k) => is_array($v) && isset($v["date"]),
                );
                $origFirst = $origFiltered->first();
                $origLast = $origFiltered->last();
                if ($origFirst && $origLast) {
                    $periods[] = [
                        "start" => $origFirst["date"],
                        "end" => $origLast["date"],
                        "nights" => $origFiltered->count(),
                    ];
                }
            }
            $periods[] = [
                "start" => $currentCheckOut->toDateString(),
                "end" => $newCheckOut->copy()->subDay()->toDateString(),
                "nights" => $additionalDays,
                "label" => "Perpanjangan",
            ];
            $newBreakdown["periods"] = $periods;

            $newBasePrice = $booking->base_price + $additionalCost;
            $newTotalPrice = $booking->total_price + $additionalCost;

            $booking->update([
                "check_out" => $newCheckOut,
                "base_price" => $newBasePrice,
                "total_price" => $newTotalPrice,
                "pricing_breakdown" => $newBreakdown,
                "payment_status" =>
                    $booking->payment_status === "paid"
                        ? "partial"
                        : $booking->payment_status,
            ]);

            Transaction::where("booking_id", $booking->id)
                ->where("reference_id", "BOOK-" . $booking->id)
                ->update(["amount" => $newTotalPrice]);

        $msg = "Booking diperpanjang {$extensionInfo} sampai {$newCheckOut->format("d M Y")}. Biaya tambahan: Rp " . number_format($additionalCost, 0, ",", ".");
        return $this->ajaxOrRedirect($msg, route("bookings.show", $booking->id));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            return back()->with("error", $e->getMessage());
        }
    }
}
