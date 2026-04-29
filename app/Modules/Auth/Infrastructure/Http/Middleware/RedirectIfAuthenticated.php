<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Guest middleware: prevents already-authenticated users from accessing guest-only endpoints
 * such as login and register. Returns 409 Conflict if the request carries a valid API token.
 *
 * Usage in routes: ->middleware('guest')
 */
class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, string ...$guards): HttpResponse
    {
        if (empty($guards)) {
            $configuredGuards = config('auth_context.guards.guest');

            if (is_string($configuredGuards) && str_contains($configuredGuards, ',')) {
                $guards = array_values(array_filter(array_map('trim', explode(',', $configuredGuards))));
            } elseif (is_string($configuredGuards) && $configuredGuards !== '') {
                $guards = [$configuredGuards];
            } else {
                $guards = [(string) config('auth_context.guards.api', config('auth.defaults.guard', 'api'))];
            }
        }

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                return response()->json(['message' => 'Already authenticated'], HttpResponse::HTTP_CONFLICT);
            }
        }

        return $next($request);
    }
}
