<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHousekeepingAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        
        // Allow Housekeeping or OB role or Admin role
        if (!$user->hasAnyRole(['Housekeeping', 'Housekeeping team', 'Housekeeping leader', 'OB', 'Admin', 'Manager', 'General Manager'])) {
            abort(403, 'Access denied. Housekeeping role required.');
        }
        
        return $next($request);
    }
}
