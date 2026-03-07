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
     *         Route::middleware('role:admin|manager')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return $this->unauthorizedResponse();
        }

        if ($this->isSuperAdmin($user)) {
            return $next($request);
        }

        foreach ($roles as $role) {
            if ($this->hasRole($user, $role)) {
                return $next($request);
            }
        }

        return $this->forbiddenResponse($roles);
    }

    private function isSuperAdmin(mixed $user): bool
    {
        return method_exists($user, 'hasRole') && $user->hasRole('super-admin');
    }

    private function hasRole(mixed $user, string $role): bool
    {
        return method_exists($user, 'hasRole') && $user->hasRole($role);
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
