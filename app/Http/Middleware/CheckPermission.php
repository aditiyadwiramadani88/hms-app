<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, $permission): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        $user = $request->user();

        // BYPASS: Only Super Admin (if exists) can access anything
        // Check directly in DB to avoid team-scoping issues with hasRole()
        $isSuperAdmin = \DB::table('model_has_roles')
            ->where('model_id', $user->id)
            ->where('model_type', get_class($user))
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('roles.name', 'Super Admin')
            ->exists();

        if ($isSuperAdmin) {
            return $next($request);
        }

        $hotelId = session('active_hotel_id');

        $permissions = explode('|', $permission);

        // Check any of the pipe-separated permissions
        foreach ($permissions as $perm) {
            $hasPermission = false;

            if ($hotelId) {
                $hasPermission = \DB::table('model_has_permissions')
                    ->where('model_id', $user->id)
                    ->where('model_type', 'App\Models\User')
                    ->where('hotel_id', $hotelId)
                    ->join('permissions', 'model_has_permissions.permission_id', '=', 'permissions.id')
                    ->where('permissions.name', $perm)
                    ->exists();

                if (!$hasPermission) {
                    $hasPermission = \DB::table('model_has_roles')
                        ->where('model_id', $user->id)
                        ->where('model_type', 'App\Models\User')
                        ->where('hotel_id', $hotelId)
                        ->join('role_has_permissions', 'model_has_roles.role_id', '=', 'role_has_permissions.role_id')
                        ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
                        ->where('permissions.name', $perm)
                        ->exists();
                }
            } else {
                $hasPermission = $user->can($perm);
            }

            if ($hasPermission) {
                return $next($request);
            }
        }

        abort(403, 'You do not have permission to access this resource.');
    }
}
