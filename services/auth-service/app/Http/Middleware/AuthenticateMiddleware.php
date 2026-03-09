<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as BaseAuthenticate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMiddleware extends BaseAuthenticate
{
    /**
     * Handle an incoming request supporting multiple guards.
     * Guards tried in order: api (Passport), api-jwt (JWT), web (session).
     */
    public function handle($request, Closure $next, ...$guards): Response
    {
        // Default to api guard if none specified
        if (empty($guards)) {
            $guards = ['api'];
        }

        $this->authenticate($request, $guards);

        return $next($request);
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : route('login');
    }

    /**
     * Handle unauthenticated users with a JSON response.
     */
    protected function unauthenticated($request, array $guards): void
    {
        throw new AuthenticationException(
            'Unauthenticated.',
            $guards,
            $this->redirectTo($request)
        );
    }
}
