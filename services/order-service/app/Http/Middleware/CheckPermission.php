<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Usage:  Route::middleware('permission:manage-orders')
     *         Route::middleware('permission:manage-orders|view-orders')
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            return $this->unauthorizedResponse();
        }

        if ($this->isSuperAdmin($user)) {
            return $next($request);
        }

        foreach ($permissions as $permission) {
            if ($this->hasPermission($user, $permission)) {
                return $next($request);
            }
        }

        return $this->forbiddenResponse($permissions);
    }

    private function isSuperAdmin(mixed $user): bool
    {
        return method_exists($user, 'hasRole') && $user->hasRole('super-admin');
    }

    private function hasPermission(mixed $user, string $permission): bool
    {
        return method_exists($user, 'hasPermission') && $user->hasPermission($permission);
    }

    private function unauthorizedResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated.',
        ], 401);
    }

    private function forbiddenResponse(array $permissions): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Forbidden. Required permission(s): ' . implode(', ', $permissions),
        ], 403);
    }
}
