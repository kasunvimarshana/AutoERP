<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RBAC middleware — gates routes to users that have at least one of the
 * required roles.
 *
 * Usage in routes:
 *   ->middleware('role:admin')
 *   ->middleware('role:admin,manager')  // any of the listed roles
 */
final class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        if (!$user->hasAnyRole($roles)) {
            return response()->json([
                'error'          => 'Forbidden.',
                'required_roles' => $roles,
            ], 403);
        }

        return $next($request);
    }
}
