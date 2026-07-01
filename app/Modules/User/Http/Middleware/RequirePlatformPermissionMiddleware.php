<?php

declare(strict_types=1);

namespace Modules\User\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\PlatformPermissionCheckerInterface;
use Modules\Core\Http\Responses\ApiErrorResponseFactory;
use Symfony\Component\HttpFoundation\Response;

final class RequirePlatformPermissionMiddleware
{
    public function __construct(
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly PlatformPermissionCheckerInterface $permissions,
        private readonly ApiErrorResponseFactory $responses,
    ) {}

    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $operatorId = $this->currentUser->currentUserId();
        if ($operatorId === null || ! $this->allowsAny($operatorId, $permissions)) {
            return $this->responses->make(
                'PLATFORM_PERMISSION_REQUIRED',
                'You do not have permission to perform this platform action.',
                403,
                'authorization',
                ['permissions' => $permissions],
            );
        }

        return $next($request);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function allowsAny(int $operatorId, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->permissions->allows($operatorId, $permission)) {
                return true;
            }
        }

        return false;
    }
}
