<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsOB
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->hasAnyRole(['OB', 'Housekeeping'])) {
            abort(403, 'Akses ditolak. Halaman ini hanya untuk role OB/Housekeeping.');
        }

        return $next($request);
    }
}
