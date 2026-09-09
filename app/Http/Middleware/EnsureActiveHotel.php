<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveHotel
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $hotelId = session('active_hotel_id');

            // If user has only one hotel access and session is not set, set it automatically
            if (!$hotelId && $user->hotels()->count() === 1) {
                $hotelId = $user->hotels()->first()->id;
                session(['active_hotel_id' => $hotelId]);
            }

            // Set Spatie Permission team ID before checking roles
            if ($hotelId) {
                app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
                setPermissionsTeamId($hotelId);
            }

            // Tenant users: auto-set hotel from their tenant and redirect to tenant dashboard
            if ($user->tenant_id) {
                $tenant = $user->tenant;
                if ($tenant && $tenant->hotel_id) {
                    if (!$hotelId || $hotelId !== $tenant->hotel_id) {
                        session(['active_hotel_id' => $tenant->hotel_id]);
                        $hotelId = $tenant->hotel_id;
                        setPermissionsTeamId($hotelId); // update again if changed
                    }
                    // Redirect tenant away from select-branch and hotel dashboard
                    if ($request->is('admin/select-branch*') || $request->routeIs('dashboard') || $request->is('admin/')) {
                        return redirect()->route('tenant.dashboard');
                    }
                }
            }

            // OB/Housekeeping users: redirect to OB dashboard
            if (!$user->tenant_id && $user->hasAnyRole(['OB', 'Housekeeping']) && !$user->hasRole('Admin')) {
                // We should ensure they go to housekeeping.my-tasks or ob.dashboard based on our latest logic,
                if ($request->is('admin/select-branch*')) {
                    if ($user->hasRole('OB') && !$user->hasRole('Housekeeping')) {
                        return redirect()->route('ob.dashboard');
                    } else {
                        return redirect()->route('housekeeping.my-tasks');
                    }
                }
            }

            // Super Admin might bypass if they are in Super Admin panel, 
            // but for operational data, they should also have a hotel context.
            if (!$hotelId && !$user->hasRole('Super Admin')) {
                // If the current route is not the branch selection route, redirect to it.
                if (!$request->is('admin/select-branch*') && !$request->is('admin/logout*') && !$request->is('admin/hotels*') && !$request->is('tenant*')) {
                    return redirect()->route('branch.select');
                }
            }
        }

        return $next($request);
    }
}
