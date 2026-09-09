<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\RoomType;
use App\Services\RoomService;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    use \App\Traits\AjaxResponse;

    protected RoomService $roomService;

    public function __construct(RoomService $roomService)
    {
        $this->roomService = $roomService;
    }

    /**
     * Display a listing of rooms with optional filters.
     */
    public function index(Request $request)
    {
        $query = Room::with('roomType');

        // Search by room number
        if ($request->filled('search')) {
            $query->where('room_number', 'like', '%' . $request->search . '%');
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by floor
        if ($request->filled('floor')) {
            $query->where('floor', $request->floor);
        }

        // Filter by room type
        if ($request->filled('room_type')) {
            $query->where('room_type_id', $request->room_type);
        }

        $rooms = $query->orderBy('room_number')->paginate(15);
        $roomTypes = \Illuminate\Support\Facades\Cache::remember(
            "hotel:" . active_hotel_id() . ":room_types",
            300,
            fn() => RoomType::where('is_active', true)->get()
        );

        // Floor should also be scoped, but Global Scope handles it!
        $floors = Room::distinct('floor')->orderBy('floor')->pluck('floor');

        // Same source as the add/edit form's Status select (RoomStatus master,
        // per-hotel) instead of a hardcoded list -- the old hardcoded options
        // (Occupied/Checkout/dirty/cleaning/maintenance/out_of_order) didn't
        // match any hotel's real RoomStatus vocabulary, so most of them never
        // returned results and the filter couldn't reach real values like
        // "In-House" or hotel 2/3/4's Indonesian statuses at all.
        $statuses = \Illuminate\Support\Facades\Cache::remember(
            "hotel:" . active_hotel_id() . ":room_statuses",
            300,
            fn() => \App\Models\RoomStatus::orderBy('display_order')->get()
        );

        return view('rooms.index', compact('rooms', 'roomTypes', 'floors', 'statuses'));
    }

    /**
     * Show the form for creating a new room.
     */
    public function create()
    {
        $roomTypes = \Illuminate\Support\Facades\Cache::remember(
            "hotel:" . active_hotel_id() . ":room_types",
            300,
            fn() => RoomType::where('is_active', true)->get()
        );
        $statuses = \Illuminate\Support\Facades\Cache::remember(
            "hotel:" . active_hotel_id() . ":room_statuses",
            300,
            fn() => \App\Models\RoomStatus::orderBy('display_order')->get()
        );

        return view('rooms.create', compact('roomTypes', 'statuses'));
    }

    /**
     * Store a newly created room in storage.
     */
    public function store(Request $request)
    {
        $statuses = \App\Models\RoomStatus::pluck('name')->toArray();

        $validated = $request->validate([
            'room_number' => [
                'required',
                'string',
                'max:10',
                Rule::unique('rooms')->where('hotel_id', active_hotel_id()),
            ],
            'room_type_id' => 'required|exists:room_types,id',
            'floor' => 'required|integer|min:1',
            'status' => 'required|in:'.implode(',', $statuses),
            'price_public' => 'required|numeric|min:0',
            'price_breakfast_public' => 'nullable|numeric|min:0|max:9999999999.99',
            'price_sales' => 'required|numeric|min:0',
            'price_breakfast_sales' => 'nullable|numeric|min:0|max:9999999999.99',
            'price_high_season' => 'required|numeric|min:0',
            'price_breakfast_high_season' => 'nullable|numeric|min:0|max:9999999999.99',
            'is_kos' => 'nullable|boolean',
            'price_kos' => 'required_if:is_kos,1|nullable|numeric|min:0',
            'yearly_price' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'price_extra_person' => 'nullable|numeric|min:0',
            'max_occupancy' => 'nullable|integer|min:1|max:20',
            'notes' => 'nullable|string|max:500',
        ]);

        $validated['is_kos'] = $request->has('is_kos');

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('rooms', 'public');
        }

        $room = Room::create($validated);

        return $this->ajaxOrRedirect('Room created successfully.', route('rooms.index'), $room, 201);
    }

    /**
     * Display the specified room.
     */
    public function show(Room $room)
    {
        $room->load(['roomType', 'bookings.guest', 'maintenanceLogs']);

        return view('rooms.show', compact('room'));
    }

    /**
     * Show the form for editing the specified room.
     */
    public function edit(Room $room)
    {
        $roomTypes = \Illuminate\Support\Facades\Cache::remember(
            "hotel:" . active_hotel_id() . ":room_types",
            300,
            fn() => RoomType::where('is_active', true)->get()
        );
        $statuses = \Illuminate\Support\Facades\Cache::remember(
            "hotel:" . active_hotel_id() . ":room_statuses",
            300,
            fn() => \App\Models\RoomStatus::orderBy('display_order')->get()
        );
        $checklistTemplates = \App\Models\CleaningChecklistTemplate::active()->get();
        $selectedChecklistIds = $room->checklistTemplates()->pluck('cleaning_checklist_templates.id')->toArray();

        $room->load('kostPricingTiers');

        return view('rooms.edit', compact('room', 'roomTypes', 'statuses', 'checklistTemplates', 'selectedChecklistIds'));
    }

    /**
     * Update the specified room in storage.
     */
    public function update(Request $request, Room $room)
    {
        $statuses = \App\Models\RoomStatus::pluck('name')->toArray();

        $validated = $request->validate([
            'room_number' => [
                'required', 'string', 'max:10',
                Rule::unique('rooms')->where('hotel_id', active_hotel_id())->ignore($room->id),
            ],
            'room_type_id' => 'required|exists:room_types,id',
            'floor' => 'required|integer|min:1',
            'status' => 'required|in:'.implode(',', $statuses),
            'price_public' => 'required|numeric|min:0',
            'price_breakfast_public' => 'nullable|numeric|min:0|max:9999999999.99',
            'price_sales' => 'required|numeric|min:0',
            'price_breakfast_sales' => 'nullable|numeric|min:0|max:9999999999.99',
            'price_high_season' => 'required|numeric|min:0',
            'price_breakfast_high_season' => 'nullable|numeric|min:0|max:9999999999.99',
            'is_kos' => 'nullable|boolean',
            'price_kos' => 'required_if:is_kos,1|nullable|numeric|min:0',
            'yearly_price' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'price_extra_person' => 'nullable|numeric|min:0',
            'max_occupancy' => 'nullable|integer|min:1|max:20',
            'notes' => 'nullable|string|max:500',
            'checklist_items' => 'nullable|array',
            'checklist_items.*' => 'exists:cleaning_checklist_templates,id',
        ]);

        $validated['is_kos'] = $request->has('is_kos');

        if ($request->hasFile('image')) {
            if ($room->image) {
                Storage::disk('public')->delete($room->image);
            }
            $validated['image'] = $request->file('image')->store('rooms', 'public');
        }

        $room->update($validated);

        if ($request->has('checklist_items')) {
            $room->checklistTemplates()->sync(array_unique($request->checklist_items));
        } else {
            $room->checklistTemplates()->detach();
        }

        // Sync pricing tiers
        if ($request->has('pricing_tiers')) {
            $room->kostPricingTiers()->delete();
            foreach ($request->pricing_tiers as $tier) {
                if (!empty($tier['duration_months'])) {
                    $room->kostPricingTiers()->create([
                        'hotel_id' => active_hotel_id(),
                        'duration_months' => $tier['duration_months'],
                        'discount_type' => $tier['discount_type'],
                        'fixed_price' => $tier['discount_type'] === 'fixed' ? ($tier['fixed_price'] ?? null) : null,
                        'percentage_value' => $tier['discount_type'] === 'percentage' ? ($tier['percentage_value'] ?? null) : null,
                    ]);
                }
            }
        }

        return $this->ajaxOrRedirect('Room updated successfully.', route('rooms.index'), $room);
    }

    /**
     * Remove the specified room from storage.
     */
    public function destroy(Room $room)
    {
        if ($room->bookings()->whereIn('status', ['checked_in', 'confirmed', 'pending'])->exists()) {
            if ($this->isAjaxRequest()) return $this->ajaxError('Cannot delete room with active bookings.');
            return redirect()->route('rooms.index')
                ->with('error', 'Cannot delete room with active bookings.');
        }

        $room->delete();

        return $this->ajaxOrRedirect('Room deleted successfully.', route('rooms.index'));
    }

    /**
     * Update room status (AJAX).
     */
    public function updateStatus(Request $request, Room $room)
    {
        $statuses = \App\Models\RoomStatus::pluck('name')->toArray();

        $validated = $request->validate([
            'status' => 'required|in:'.implode(',', $statuses),
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $room = $this->roomService->updateRoomStatus(
                $room,
                $validated['status'],
                $validated['reason'] ?? null
            );

            return $this->ajaxSuccess('Room status updated successfully.', $room->only(['id', 'room_number', 'status']));
        } catch (\Exception $e) {
            return $this->ajaxError($e->getMessage());
        }
    }
}
