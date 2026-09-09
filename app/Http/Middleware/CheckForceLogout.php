<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckForceLogout
{
    /**
     * Check if user has been force-logged-out by admin.
     * Sets a shared variable for the view to show SweetAlert popup.
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return $next($request);
        }

        // Skip for AJAX/API requests that handle force logout reset
        if ($request->routeIs('force-logout.reset')) {
            return $next($request);
        }

        $user = Auth::user();

        // Check force logout flag directly from DB (not cached model)
        $dbUser = DB::table('users')->where('id', $user->id)->select('is_force_logout')->first();

        if ($dbUser && $dbUser->is_force_logout) {
            // Share with all views
            view()->share('force_logout', true);
        } else {
            view()->share('force_logout', false);
        }

        return $next($request);
    }
}
