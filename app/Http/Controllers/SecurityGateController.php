<?php

namespace App\Http\Controllers;

use App\Models\GuestVehicle;
use App\Models\VehicleLog;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SecurityGateController extends Controller
{
    use \App\Traits\AjaxResponse;

    public function dashboard(Request $request)
    {
        $hotelId = active_hotel_id();
        $date = $request->get('date', now()->toDateString());

        $vehiclesInside = VehicleLog::where('hotel_id', $hotelId)
            ->where('status', 'in')
            ->whereDate('time_in', $date)
            ->with(['guestVehicle.guest:id,name', 'booking.room:id,room_number'])
            ->latest('time_in')
            ->get();

        $needExitPhoto = VehicleLog::where('hotel_id', $hotelId)
            ->where('status', 'out')
            ->whereNull('photo_out')
            ->whereDate('time_out', $date)
            ->with(['guestVehicle.guest:id,name', 'booking.room:id,room_number'])
            ->latest('time_out')
            ->get();

        $stats = [
            'inside_count' => $vehiclesInside->count(),
            'need_photo_count' => $needExitPhoto->count(),
        ];

        return view('security-gate.dashboard', compact('vehiclesInside', 'needExitPhoto', 'stats', 'date'));
    }

    public function createEntry()
    {
        return view('security-gate.entry');
    }

    public function storeEntry(Request $request)
    {
        try {
            $validated = $request->validate([
                'plate_number' => 'required|string|max:20',
                'driver_name' => 'nullable|string|max:255',
                'vehicle_type' => 'nullable|in:motor,mobil,truck',
                'vehicle_color' => 'nullable|string|max:50',
                'purpose' => 'nullable|in:menginap,kunjungan,delivery,karyawan,lainnya',
                'destination_room' => 'nullable|string|max:50',
                'photo_in' => 'nullable',
                'photo_in.*' => 'nullable|image|mimes:jpeg,png|max:20480',
                'notes' => 'nullable|string',
                'guest_vehicle_id' => 'nullable|integer|exists:guest_vehicles,id',
                'booking_id' => 'nullable|integer|exists:bookings,id',
            ]);

            $photoPaths = [];
            if ($request->hasFile('photo_in')) {
                $files = is_array($request->file('photo_in')) ? $request->file('photo_in') : [$request->file('photo_in')];
                foreach ($files as $file) {
                    $photoPaths[] = $this->compressAndStorePhoto($file, 'in');
                }
            }

            $vehicleLog = VehicleLog::create([
                'hotel_id' => active_hotel_id(),
                'guest_vehicle_id' => $validated['guest_vehicle_id'] ?? null,
                'booking_id' => $validated['booking_id'] ?? null,
                'driver_name' => $validated['driver_name'] ?? null,
                'plate_number' => $validated['plate_number'],
                'vehicle_type' => $validated['vehicle_type'] ?? 'mobil',
                'vehicle_color' => $validated['vehicle_color'] ?? null,
                'photo_in' => !empty($photoPaths) ? $photoPaths : null,
                'purpose' => $validated['purpose'] ?? 'menginap',
                'destination_room' => $validated['destination_room'] ?? null,
                'time_in' => now(),
                'status' => 'in',
                'security_in_id' => auth()->id(),
                'notes' => $validated['notes'] ?? null,
            ]);

            return $this->ajaxOrRedirect("Kendaraan {$vehicleLog->plate_number} berhasil dicatat masuk.", route('security-gate.dashboard'), $vehicleLog, 201);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            throw $e;
        }
    }

    public function exitList(Request $request)
    {
        $hotelId = active_hotel_id();
        $date = $request->get('date', now()->toDateString());

        $query = VehicleLog::where('hotel_id', $hotelId)
            ->where('status', 'in')
            ->whereDate('time_in', $date)
            ->with('guestVehicle.guest')
            ->latest('time_in');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('plate_number', 'like', "%{$search}%")
                  ->orWhere('driver_name', 'like', "%{$search}%");
            });
        }

        $vehicles = $query->get();

        return view('security-gate.exit', compact('vehicles'));
    }

    public function processExit(Request $request, VehicleLog $vehicleLog)
    {
        try {
            if ($vehicleLog->status !== 'in') {
                $errorMessage = 'Kendaraan ini sudah tercatat keluar.';
                if ($this->isAjaxRequest()) {
                    return $this->ajaxError($errorMessage);
                }
                return redirect()->back()->with('error', $errorMessage);
            }

            $request->validate([
                'photo_out' => 'nullable',
                'photo_out.*' => 'nullable|image|mimes:jpeg,png|max:20480',
            ]);

            $existingPhotos = is_array($vehicleLog->photo_out) ? $vehicleLog->photo_out : ($vehicleLog->photo_out ? [$vehicleLog->photo_out] : []);
            
            $newPhotos = [];
            if ($request->hasFile('photo_out')) {
                $files = is_array($request->file('photo_out')) ? $request->file('photo_out') : [$request->file('photo_out')];
                foreach ($files as $file) {
                    $newPhotos[] = $this->compressAndStorePhoto($file, 'out');
                }
            }

            $allPhotos = array_filter(array_merge($existingPhotos, $newPhotos));

            $vehicleLog->update([
                'status' => 'out',
                'time_out' => now(),
                'security_out_id' => auth()->id(),
                'photo_out' => !empty($allPhotos) ? array_values($allPhotos) : null,
            ]);

            return $this->ajaxOrRedirect("Kendaraan {$vehicleLog->plate_number} berhasil dicatat keluar.", route('security-gate.dashboard'), $vehicleLog);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            throw $e;
        }
    }

    public function attachExitPhoto(Request $request, VehicleLog $vehicleLog)
    {
        try {
            if ($vehicleLog->status !== 'out') {
                $errorMessage = 'Kendaraan ini masih berstatus masuk.';
                if ($this->isAjaxRequest()) {
                    return $this->ajaxError($errorMessage);
                }
                return redirect()->back()->with('error', $errorMessage);
            }

            $request->validate([
                'photo_out' => 'required',
                'photo_out.*' => 'image|mimes:jpeg,png|max:20480',
            ]);

            $existingPhotos = is_array($vehicleLog->photo_out) ? $vehicleLog->photo_out : ($vehicleLog->photo_out ? [$vehicleLog->photo_out] : []);
            
            $newPhotos = [];
            if ($request->hasFile('photo_out')) {
                $files = is_array($request->file('photo_out')) ? $request->file('photo_out') : [$request->file('photo_out')];
                foreach ($files as $file) {
                    $newPhotos[] = $this->compressAndStorePhoto($file, 'out');
                }
            }

            $allPhotos = array_filter(array_merge($existingPhotos, $newPhotos));

            $vehicleLog->update([
                'photo_out' => !empty($allPhotos) ? array_values($allPhotos) : null,
                'security_out_id' => $vehicleLog->security_out_id ?? auth()->id(),
            ]);

            return $this->ajaxOrRedirect('Foto keluar berhasil dilampirkan.', route('security-gate.dashboard'), $vehicleLog);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            throw $e;
        }
    }

    public function searchPlate(Request $request)
    {
        $request->validate([
            'plate' => 'required|string|min:3|max:20',
        ]);

        $hotelId = active_hotel_id();
        $plate = $request->plate;

        $guestVehicle = GuestVehicle::where('hotel_id', $hotelId)
            ->where('plate_number', 'like', "%{$plate}%")
            ->where('is_active', true)
            ->with('guest.bookings', function ($q) {
                $q->whereIn('status', ['confirmed', 'checked_in'])
                  ->whereDate('check_in', '<=', now())
                  ->whereDate('check_out', '>=', now())
                  ->with('room');
            })
            ->first();

        if (!$guestVehicle || !$guestVehicle->guest) {
            return response()->json([
                'found' => false,
                'message' => 'Kendaraan tidak terdaftar. Silakan lanjutkan sebagai Visitor.',
            ]);
        }

        $guest = $guestVehicle->guest;
        $activeBooking = $guest->bookings->first();

        return response()->json([
            'found' => true,
            'message' => 'Kendaraan terdaftar.',
            'guest_vehicle_id' => $guestVehicle->id,
            'guest' => [
                'name' => $guest->name,
                'phone' => $guest->phone,
            ],
            'booking' => $activeBooking ? [
                'id' => $activeBooking->id,
                'room_number' => $activeBooking->room?->room_number ?? $activeBooking->custom_room_name ?? '-',
                'check_in' => $activeBooking->check_in->format('d/m/Y'),
                'check_out' => $activeBooking->check_out->format('d/m/Y'),
                'status' => $activeBooking->status,
            ] : null,
        ]);
    }

    /**
     * Edit vehicle log entry (within 24 hours and own entry only).
     */
    public function update(Request $request, VehicleLog $vehicleLog)
    {
        try {
            $isAdmin = auth()->user()->hasRole('Admin') || auth()->user()->can('manage system');
            if (!$isAdmin) {
                if ($vehicleLog->security_in_id !== auth()->id()) {
                    throw new \Exception('Anda hanya dapat mengedit data kendaraan yang Anda input sendiri.');
                }
                if ($vehicleLog->created_at->diffInHours(now()) >= 24) {
                    throw new \Exception('Data kendaraan hanya dapat diedit dalam 24 jam setelah input.');
                }
            }

            $validated = $request->validate([
                'plate_number' => 'required|string|max:20',
                'destination_room' => 'nullable|string|max:50',
                'purpose' => 'nullable|in:menginap,kunjungan,delivery,karyawan,lainnya',
                'notes' => 'nullable|string',
                'photo_in' => 'nullable',
                'photo_in.*' => 'nullable|image|mimes:jpeg,png|max:20480',
            ]);

            $existingPhotos = is_array($vehicleLog->photo_in) ? $vehicleLog->photo_in : ($vehicleLog->photo_in ? [$vehicleLog->photo_in] : []);
            
            $newPhotos = [];
            if ($request->hasFile('photo_in')) {
                $files = is_array($request->file('photo_in')) ? $request->file('photo_in') : [$request->file('photo_in')];
                foreach ($files as $file) {
                    $newPhotos[] = $this->compressAndStorePhoto($file, 'in');
                }
            }

            $allPhotos = array_filter(array_merge($existingPhotos, $newPhotos));

            $vehicleLog->update([
                'plate_number' => $validated['plate_number'],
                'destination_room' => $validated['destination_room'] ?? $vehicleLog->destination_room,
                'purpose' => $validated['purpose'] ?? $vehicleLog->purpose,
                'notes' => $validated['notes'] ?? null,
                'photo_in' => !empty($allPhotos) ? array_values($allPhotos) : null,
            ]);

            \App\Models\AuditLog::log(
                'vehicle_log.updated',
                "Vehicle log #{$vehicleLog->id} ({$vehicleLog->plate_number}) updated by User #" . auth()->id(),
                $vehicleLog
            );

            return $this->ajaxOrRedirect('Data kendaraan berhasil diperbarui.', route('security-gate.dashboard'), $vehicleLog);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Delete vehicle log entry (within 24 hours and own entry only).
     */
    public function destroy(VehicleLog $vehicleLog)
    {
        try {
            if ($vehicleLog->security_in_id !== auth()->id()) {
                throw new \Exception('Anda hanya dapat menghapus data kendaraan yang Anda input sendiri.');
            }
            if ($vehicleLog->created_at->diffInHours(now()) >= 24) {
                throw new \Exception('Data kendaraan hanya dapat dihapus dalam 24 jam setelah input.');
            }

            // Delete associated photos from storage
            $photosIn = is_array($vehicleLog->photo_in) ? $vehicleLog->photo_in : [];
            $photosOut = is_array($vehicleLog->photo_out) ? $vehicleLog->photo_out : [];
            foreach (array_merge($photosIn, $photosOut) as $photo) {
                Storage::disk('public')->delete($photo);
            }

            $plate = $vehicleLog->plate_number;
            $vehicleLog->delete();

            \App\Models\AuditLog::log(
                'vehicle_log.deleted',
                "Vehicle log ({$plate}) deleted by User #" . auth()->id(),
                null
            );

            return $this->ajaxOrRedirect("Data kendaraan {$plate} berhasil dihapus.", route('security-gate.dashboard'), null);
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    protected function compressAndStorePhoto($file, string $suffix = 'in'): string
    {
        $hotelId = active_hotel_id();
        $uuid = (string) Str::uuid();
        $extension = $file->getClientOriginalExtension();
        $filename = "{$uuid}_{$suffix}.{$extension}";
        $directory = "vehicle-photos/{$hotelId}/" . now()->format('Y/m');
        $relativePath = "{$directory}/{$filename}";
        $fullPath = storage_path("app/public/{$relativePath}");

        if (!is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        $img = null;
        if ($extension === 'png') {
            $img = @imagecreatefrompng($file->getRealPath());
        } else {
            $img = @imagecreatefromjpeg($file->getRealPath());
        }

        if ($img) {
            $quality = 80;
            if ($extension === 'png') {
                imagepng($img, $fullPath, 6);
            } else {
                imagejpeg($img, $fullPath, $quality);
            }
            imagedestroy($img);

            // If still > 1MB, reduce quality iteratively
            if (filesize($fullPath) > 1048576) {
                $img2 = null;
                if ($extension === 'png') {
                    $img2 = imagecreatefrompng($fullPath);
                    for ($level = 7; $level <= 9 && filesize($fullPath) > 1048576; $level++) {
                        imagepng($img2, $fullPath, $level);
                    }
                    if ($img2) imagedestroy($img2);
                } else {
                    $img2 = imagecreatefromjpeg($fullPath);
                    for ($q = 70; $q >= 10 && filesize($fullPath) > 1048576; $q -= 10) {
                        imagejpeg($img2, $fullPath, $q);
                    }
                    if ($img2) imagedestroy($img2);
                }
            }
        } else {
            $file->move(dirname($fullPath), $filename);
        }

        return $relativePath;
    }
}
