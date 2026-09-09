<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Check if user has tenant role OR tenant_id
        if (!$user->hasRole('Tenant') && !$user->tenant_id) {
            abort(403, 'Access denied. Tenant role required.');
        }

        // Check if user has tenant_id
        if (!$user->tenant_id) {
            abort(403, 'User is not associated with any tenant.');
        }

        // Load tenant relationship
        $tenant = $user->tenant;

        if (!$tenant) {
            abort(403, 'Tenant not found.');
        }

        // Check if tenant is active
        if (!$tenant->is_active) {
            return redirect()->route('tenant.inactive')->with('error', 'Your tenant account is currently inactive.');
        }

        // Check if contract is expired
        if ($tenant->isContractExpired()) {
            return redirect()->route('tenant.inactive')->with('error', 'Your tenant contract has expired.');
        }

        // Inject tenant into request for use in controllers
        $request->merge(['tenant' => $tenant]);

        return $next($request);
    }
}
