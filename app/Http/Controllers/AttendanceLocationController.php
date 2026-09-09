<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLocation;
use Illuminate\Http\Request;

class AttendanceLocationController extends Controller
{
    public function index()
    {
        $hotelId = active_hotel_id();
        $locations = AttendanceLocation::where('hotel_id', $hotelId)
            ->orderBy('name')
            ->get();

        return view('attendance.locations', compact('locations'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius_meters' => 'required|integer|min:20|max:1000',
            'is_active' => 'boolean',
        ]);

        AttendanceLocation::create([
            'hotel_id' => active_hotel_id(),
            'name' => $validated['name'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'radius_meters' => $validated['radius_meters'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json(['success' => true, 'message' => 'Lokasi absensi berhasil ditambahkan.']);
    }

    public function update(Request $request, AttendanceLocation $attendanceLocation)
    {
        $this->authorizeHotel($attendanceLocation);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius_meters' => 'required|integer|min:20|max:1000',
            'is_active' => 'boolean',
        ]);

        $attendanceLocation->update([
            'name' => $validated['name'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'radius_meters' => $validated['radius_meters'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json(['success' => true, 'message' => 'Lokasi absensi berhasil diperbarui.']);
    }

    public function destroy(AttendanceLocation $attendanceLocation)
    {
        $this->authorizeHotel($attendanceLocation);

        $hasAttendances = \App\Models\Attendance::where(function ($q) use ($attendanceLocation) {
            $q->where('check_in_location_id', $attendanceLocation->id)
              ->orWhere('check_out_location_id', $attendanceLocation->id);
        })->exists();

        if ($hasAttendances) {
            return response()->json([
                'success' => false,
                'message' => 'Lokasi tidak dapat dihapus karena sudah digunakan pada data absensi.',
            ], 422);
        }

        $attendanceLocation->delete();

        return response()->json(['success' => true, 'message' => 'Lokasi absensi berhasil dihapus.']);
    }

    protected function authorizeHotel(AttendanceLocation $location): void
    {
        if ($location->hotel_id !== active_hotel_id()) {
            abort(403, 'Unauthorized action.');
        }
    }
}
