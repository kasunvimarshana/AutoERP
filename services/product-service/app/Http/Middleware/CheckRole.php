<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Usage:  Route::middleware('role:admin')
     *         Route::middleware('role:admin|manager')  (any of the listed roles)
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        if (! $user) {
            return $this->unauthorizedResponse();
        }

        // Super-admin bypasses all role checks
        if ($user->hasRole('super-admin')) {
            return $next($request);
        }

        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        return $this->forbiddenResponse($roles);
    }

    private function unauthorizedResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated.',
        ], 401);
    }

    private function forbiddenResponse(array $roles): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Forbidden. Required role(s): ' . implode(', ', $roles),
        ], 403);
    }
}
