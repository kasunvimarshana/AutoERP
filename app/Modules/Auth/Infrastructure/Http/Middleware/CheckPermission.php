<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Permission middleware: verifies the authenticated user has the given permission.
 *
 * Usage in routes: ->middleware('permission:manage-users')
 */
class CheckPermission
{
    public function __construct(
        private readonly AuthorizationServiceInterface $authorizationService,
    ) {}

    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], HttpResponse::HTTP_UNAUTHORIZED);
        }

        foreach ($permissions as $permission) {
            if ($this->authorizationService->hasPermission($user->getAuthIdentifier(), $permission)) {
                return $next($request);
            }
        }

        return response()->json(['message' => 'Forbidden: insufficient permission'], HttpResponse::HTTP_FORBIDDEN);
    }
}
