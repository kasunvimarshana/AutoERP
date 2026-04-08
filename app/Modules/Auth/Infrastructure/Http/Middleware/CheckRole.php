<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * RBAC middleware: verifies the authenticated user has the given role.
 *
 * Usage in routes: ->middleware('role:admin')
 */
class CheckRole
{
    public function __construct(
        private readonly AuthorizationServiceInterface $authorizationService,
    ) {}

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        foreach ($roles as $role) {
            if ($this->authorizationService->hasRole($user->getAuthIdentifier(), $role)) {
                return $next($request);
            }
        }

        return response()->json(['message' => 'Forbidden: insufficient role'], 403);
    }
}
