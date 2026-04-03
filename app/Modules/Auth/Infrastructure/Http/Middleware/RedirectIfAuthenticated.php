<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guest middleware: prevents already-authenticated users from accessing guest-only endpoints
 * such as login and register. Returns 409 Conflict if the request carries a valid API token.
 *
 * Usage in routes: ->middleware('guest')
 */
class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? ['api'] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                return response()->json(['message' => 'Already authenticated'], 409);
            }
        }

        return $next($request);
    }
}
