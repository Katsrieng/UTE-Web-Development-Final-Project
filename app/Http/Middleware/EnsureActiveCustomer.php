<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        // Used only after auth and role:customer on customer Booking routes.
        abort_unless($request->user()->is_active, 403, 'Your account is inactive. Please contact the hotel administrator.');

        return $next($request);
    }
}
