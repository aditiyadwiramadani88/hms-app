<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBookingTimeLimit
{
    public function handle(Request $request, Closure $next, string $action): Response
    {
        $booking = $request->route('booking');
        $user = auth()->user();

        if (!$booking || !$user) {
            abort(403);
        }

        if ($user->hasRole('Admin') || $user->hasPermissionTo("bookings.{$action}.unlimited")) {
            return $next($request);
        }

        if ($user->hasPermissionTo("bookings.{$action}")) {
            if ($booking->created_at->diffInHours(now()) < 24) {
                return $next($request);
            }

            if ($request->wantsJson()) {
                return response()->json(['error' => 'Waktu edit/hapus sudah habis (24 jam).'], 403);
            }
            return back()->with('error', 'Waktu edit/hapus sudah habis (24 jam). Hubungi admin.');
        }

        abort(403);
    }
}
