<?php

namespace App\Http\Middleware;

use App\Models\User;
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

        $user = User::find($payload['sub']);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 401);
        }

        $request->attributes->set('auth_user', $user->toArray());
        $request->attributes->set('auth_payload', $payload);

        return $next($request);
    }
}
