<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Guest;
use App\Models\GuestVehicle;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class GuestVehicleController extends Controller
{
    use \App\Traits\AjaxResponse;

    public function index(Request $request)
    {
        $hotelId = active_hotel_id();

        $query = GuestVehicle::where('hotel_id', $hotelId)
            ->with('guest')
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('plate_number', 'like', "%{$search}%")
                  ->orWhere('owner_name', 'like', "%{$search}%")
                  ->orWhere('vehicle_brand', 'like', "%{$search}%")
                  ->orWhereHas('guest', function ($g) use ($search) {
                      $g->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('vehicle_type')) {
            $query->where('vehicle_type', $request->vehicle_type);
        }

        if ($request->filled('status_filter')) {
            $query->where('is_active', $request->status_filter === 'active');
        }

        $vehicles = $query->paginate(20)->withQueryString();

        return view('guest-vehicles.index', compact('vehicles'));
    }

    public function store(Request $request)
    {
        try {
            $hotelId = active_hotel_id();

            $validated = $request->validate([
                'plate_number' => [
                    'required', 'string', 'max:20',
                    Rule::unique('guest_vehicles')->where(function ($q) use ($hotelId) {
                        $q->where('hotel_id', $hotelId)->where('is_active', true);
                    }),
                ],
                'guest_id' => 'nullable|integer|exists:guests,id',
                'vehicle_type' => 'required|in:motor,mobil,truck',
                'vehicle_brand' => 'nullable|string|max:100',
                'vehicle_color' => 'nullable|string|max:50',
                'owner_name' => 'nullable|string|max:255',
                'notes' => 'nullable|string',
            ]);

            $vehicle = GuestVehicle::create([
                'hotel_id' => $hotelId,
                'guest_id' => $validated['guest_id'] ?? null,
                'plate_number' => $validated['plate_number'],
                'vehicle_type' => $validated['vehicle_type'],
                'vehicle_brand' => $validated['vehicle_brand'] ?? null,
                'vehicle_color' => $validated['vehicle_color'] ?? null,
                'owner_name' => $validated['owner_name'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'is_active' => true,
            ]);

            return $this->ajaxOrRedirect('Kendaraan berhasil ditambahkan.', route('guest-vehicles.index'), $vehicle, 201);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            throw $e;
        }
    }

    public function update(Request $request, GuestVehicle $guestVehicle)
    {
        try {
            // Security: only can edit own entries within 24 hours
            if ($guestVehicle->user_id !== Auth::id()) {
                throw new \Exception('Anda hanya dapat mengedit data kendaraan yang Anda input sendiri.');
            }
            if ($guestVehicle->created_at->diffInHours(now()) >= 24) {
                throw new \Exception('Data kendaraan hanya dapat diedit dalam 24 jam setelah input.');
            }

            $hotelId = active_hotel_id();

            $validated = $request->validate([
                'plate_number' => [
                    'required', 'string', 'max:20',
                    Rule::unique('guest_vehicles')->where(function ($q) use ($hotelId) {
                        $q->where('hotel_id', $hotelId)->where('is_active', true);
                    })->ignore($guestVehicle->id),
                ],
                'guest_id' => 'nullable|integer|exists:guests,id',
                'vehicle_type' => 'required|in:motor,mobil,truck',
                'vehicle_brand' => 'nullable|string|max:100',
                'vehicle_color' => 'nullable|string|max:50',
                'owner_name' => 'nullable|string|max:255',
                'notes' => 'nullable|string',
            ]);

            $guestVehicle->update([
                'guest_id' => $validated['guest_id'] ?? null,
                'plate_number' => $validated['plate_number'],
                'vehicle_type' => $validated['vehicle_type'],
                'vehicle_brand' => $validated['vehicle_brand'] ?? null,
                'vehicle_color' => $validated['vehicle_color'] ?? null,
                'owner_name' => $validated['owner_name'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            AuditLog::log(
                'vehicle.updated',
                "Kendaraan {$guestVehicle->plate_number} diupdate oleh User #" . Auth::id(),
                $guestVehicle
            );

            return $this->ajaxOrRedirect('Kendaraan berhasil diperbarui.', route('guest-vehicles.index'), $guestVehicle);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function show(GuestVehicle $guestVehicle)
    {
        $guestVehicle->load('guest');

        return response()->json([
            'id' => $guestVehicle->id,
            'plate_number' => $guestVehicle->plate_number,
            'vehicle_type' => $guestVehicle->vehicle_type,
            'vehicle_brand' => $guestVehicle->vehicle_brand,
            'vehicle_color' => $guestVehicle->vehicle_color,
            'owner_name' => $guestVehicle->owner_name,
            'guest_id' => $guestVehicle->guest_id,
            'guest_name' => $guestVehicle->guest?->name,
            'notes' => $guestVehicle->notes,
            'is_active' => $guestVehicle->is_active,
        ]);
    }

    public function destroy(GuestVehicle $guestVehicle)
    {
        try {
            $guestVehicle->update(['is_active' => false]);

            return $this->ajaxOrRedirect('Kendaraan berhasil dinonaktifkan.', route('guest-vehicles.index'));
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            throw $e;
        }
    }

    public function byGuest(Guest $guest)
    {
        $hotelId = active_hotel_id();

        $vehicles = GuestVehicle::where('hotel_id', $hotelId)
            ->where('guest_id', $guest->id)
            ->where('is_active', true)
            ->get(['id', 'plate_number', 'vehicle_type', 'vehicle_brand', 'vehicle_color']);

        return response()->json($vehicles);
    }
}
