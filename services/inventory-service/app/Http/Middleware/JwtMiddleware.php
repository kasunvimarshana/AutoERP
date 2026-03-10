<?php

namespace App\Http\Middleware;

use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtMiddleware
{
    public function __construct(private readonly JwtService $jwt) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Authorization token not provided',
            ], 401);
        }

        try {
            $payload = $this->jwt->validateToken($token);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 401);
        }

        if (($payload['type'] ?? '') !== 'access') {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token type',
            ], 401);
        }

        $request->attributes->set('auth_user', [
            'id'    => $payload['sub'],
            'email' => $payload['email'] ?? null,
            'role'  => $payload['role'] ?? 'user',
        ]);
        $request->attributes->set('auth_payload', $payload);

        return $next($request);
    }
}
