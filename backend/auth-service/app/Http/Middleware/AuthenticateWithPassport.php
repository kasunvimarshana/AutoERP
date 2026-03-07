<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateWithPassport
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            if (!$request->bearerToken()) {
                throw new AuthenticationException('No token provided.');
            }

            $user = auth('api')->authenticate();

            if (!$user) {
                throw new AuthenticationException('Invalid token.');
            }

            return $next($request);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
    }
}
