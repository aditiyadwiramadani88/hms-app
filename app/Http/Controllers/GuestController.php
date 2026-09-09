<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GuestController extends Controller
{
    use \App\Traits\AjaxResponse;
    /**
     * Display a listing of guests.
     */
    public function index(Request $request)
    {
        $query = Guest::query();

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->filled('email')) {
            $query->where('email', 'like', '%' . $request->email . '%');
        }

        if ($request->filled('phone')) {
            $query->where('phone', 'like', '%' . $request->phone . '%');
        }

        if ($request->filled('id_number')) {
            $query->where('id_number', 'like', '%' . $request->id_number . '%');
        }

        if ($request->filled('company_name')) {
            $query->where('company_name', 'like', '%' . $request->company_name . '%');
        }

        if ($request->filled('customer_type_id')) {
            $query->where('customer_type_id', $request->customer_type_id);
        }

        if ($request->filled('guest_category_id')) {
            $query->where('guest_category_id', $request->guest_category_id);
        }

        if ($request->filled('citizenship_code')) {
            $query->where('citizenship_code', $request->citizenship_code);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('id_number', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('legacy_customer_code', 'like', "%{$search}%")
                    ->orWhere('vehicle_number', 'like', "%{$search}%");
            });
        }

        $guests = $query->with(['customerType:id,name', 'guestCategory:id,name'])
            ->select('guests.id', 'guests.name', 'guests.email', 'guests.phone', 'guests.id_number', 'guests.company_name', 'guests.guest_category_id', 'guests.customer_type_id', 'guests.legacy_customer_code', 'guests.vehicle_number')
            ->withCount('bookings')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $customerTypes = \App\Models\CustomerType::where('hotel_id', active_hotel_id())->orderBy('name')->get();
        $guestCategories = \App\Models\GuestCategory::orderBy('name')->get();

        return view('guests.index', compact('guests', 'customerTypes', 'guestCategories'));
    }

    /**
     * Show the form for creating a new guest.
     */
    public function create()
    {
        $guestCategories = \App\Models\GuestCategory::orderBy('name')->get();
        return view('guests.create', compact('guestCategories'));
    }

    /**
     * Store a newly created guest in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'guest_category_id' => 'nullable|exists:guest_categories,id',
            'customer_type_id' => 'nullable|exists:customer_types,id',
            'name' => 'required|string|max:255',
            'email' => [
                'nullable', 'string', 'max:255',
                Rule::unique('guests')->where('hotel_id', active_hotel_id())
            ],
            'phone' => 'required|string|max:50',
            'id_number' => [
                'required', 'string', 'max:100',
                Rule::unique('guests')->where('hotel_id', active_hotel_id())->ignore($request->id)
            ],
            'id_card_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'identity_type' => 'nullable|string|max:100',
            'citizenship_code' => 'nullable|string|max:10',
            'nationality' => 'nullable|string|max:100',
            'company_name' => 'nullable|string|max:255',
            'vehicle_number' => 'nullable|string|max:50',
            'reference_source' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
        ]);

        if ($request->hasFile('id_card_photo')) {
            $path = $request->file('id_card_photo')->store('guests/id_cards', 'public');
            $validated['id_card_photo'] = $path;
        }

        $guest = Guest::create($validated);

        // Sync emergency contacts
        if ($request->has('emergency_contacts')) {
            foreach ($request->emergency_contacts as $contact) {
                if (!empty($contact['contact_name']) && !empty($contact['phone_number'])) {
                    $guest->emergencyContacts()->create([
                        'hotel_id' => active_hotel_id(),
                        'contact_name' => $contact['contact_name'],
                        'phone_number' => $contact['phone_number'],
                        'relationship' => $contact['relationship'] ?? null,
                    ]);
                }
            }
        }

        return $this->ajaxOrRedirect('Guest created successfully.', route('guests.index'), $guest, 201);
    }

    /**
     * Display the specified guest.
     */
    public function show(Guest $guest)
    {
        $guest->load(['bookings.room.roomType', 'transactions', 'posOrders']);

        return view('guests.show', compact('guest'));
    }

    /**
     * Show the form for editing the specified guest.
     */
    public function edit(Guest $guest)
    {
        $guestCategories = \App\Models\GuestCategory::orderBy('name')->get();
        $guest->load('emergencyContacts');
        return view('guests.edit', compact('guest', 'guestCategories'));
    }

    /**
     * Update the specified guest in storage.
     */
    public function update(Request $request, Guest $guest)
    {
        $validated = $request->validate([
            'guest_category_id' => 'nullable|exists:guest_categories,id',
            'customer_type_id' => 'nullable|exists:customer_types,id',
            'name' => 'required|string|max:255',
            'email' => [
                'nullable', 'string', 'max:255',
                Rule::unique('guests')->where('hotel_id', active_hotel_id())->ignore($guest->id)
            ],
            'phone' => 'required|string|max:50',
            'id_number' => [
                'required', 'string', 'max:100',
                Rule::unique('guests')->where('hotel_id', active_hotel_id())->ignore($guest->id)
            ],
            'id_card_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'identity_type' => 'nullable|string|max:100',
            'citizenship_code' => 'nullable|string|max:10',
            'nationality' => 'nullable|string|max:100',
            'company_name' => 'nullable|string|max:255',
            'vehicle_number' => 'nullable|string|max:50',
            'reference_source' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
        ]);

        if ($request->hasFile('id_card_photo')) {
            // Delete old photo if exists
            if ($guest->id_card_photo) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($guest->id_card_photo);
            }
            $path = $request->file('id_card_photo')->store('guests/id_cards', 'public');
            $validated['id_card_photo'] = $path;
        }

        $guest->update($validated);

        // Sync emergency contacts
        if ($request->has('emergency_contacts')) {
            $guest->emergencyContacts()->delete();
            foreach ($request->emergency_contacts as $contact) {
                if (!empty($contact['contact_name']) && !empty($contact['phone_number'])) {
                    $guest->emergencyContacts()->create([
                        'hotel_id' => active_hotel_id(),
                        'contact_name' => $contact['contact_name'],
                        'phone_number' => $contact['phone_number'],
                        'relationship' => $contact['relationship'] ?? null,
                    ]);
                }
            }
        }

        return $this->ajaxOrRedirect('Guest updated successfully.', route('guests.index'), $guest);
    }

    /**
     * Search guests for Select2 AJAX.
     */
    public function search(Request $request)
    {
        $search = $request->q;
        $guests = Guest::where('name', 'like', "%{$search}%")
            ->orWhere('phone', 'like', "%{$search}%")
            ->orWhere('id_number', 'like', "%{$search}%")
            ->orWhere('legacy_customer_code', 'like', "%{$search}%")
            ->limit(20)
            ->get(['id', 'name', 'phone', 'id_number', 'company_name']);

        return response()->json($guests->map(function($guest) {
            $company = $guest->company_name ? " [{$guest->company_name}]" : "";
            return [
                'id' => $guest->id,
                'text' => $guest->name . $company . ' (' . ($guest->phone ?: 'No Phone') . ' - ' . ($guest->id_number ?: 'No ID') . ')'
            ];
        }));
    }

    /**
     * Remove the specified guest from storage.
     */
    public function destroy(Guest $guest)
    {
        if ($guest->bookings()->whereIn('status', ['checked_in', 'confirmed', 'pending'])->exists()) {
            if ($this->isAjaxRequest()) return $this->ajaxError('Cannot delete guest with active bookings.');
            return redirect()->route('guests.index')
                ->with('error', 'Cannot delete guest with active bookings.');
        }

        $guest->delete();

        return $this->ajaxOrRedirect('Guest deleted successfully.', route('guests.index'));
    }
}
