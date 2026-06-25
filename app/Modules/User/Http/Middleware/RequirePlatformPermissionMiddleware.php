<?php

declare(strict_types=1);

namespace Modules\User\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\PlatformPermissionCheckerInterface;
use Symfony\Component\HttpFoundation\Response;

final class RequirePlatformPermissionMiddleware
{
    public function __construct(
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly PlatformPermissionCheckerInterface $permissions,
    ) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $userId = $this->currentUser->currentUserId();
        if ($userId === null || ! $this->permissions->hasPermission($userId, $permission)) {
            return new JsonResponse([
                'message' => 'You do not have permission to perform this platform action.',
                'error' => [
                    'code' => 'PLATFORM_PERMISSION_REQUIRED',
                    'context' => ['permission' => $permission],
                ],
            ], 403);
        }

        return $next($request);
    }
}
