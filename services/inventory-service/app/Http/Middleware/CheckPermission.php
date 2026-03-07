<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Usage:  Route::middleware('permission:edit-inventory')
     *         Route::middleware('permission:edit-inventory|view-inventory')
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        if (! $user) {
            return $this->unauthorizedResponse();
        }

        // Super-admin bypasses all permission checks
        if (method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
            return $next($request);
        }

        if (method_exists($user, 'hasPermission')) {
            foreach ($permissions as $permission) {
                if ($user->hasPermission($permission)) {
                    return $next($request);
                }
            }
        }

        return $this->forbiddenResponse($permissions);
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
