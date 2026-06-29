<?php

declare(strict_types=1);

namespace Modules\User\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\PermissionCheckerInterface;
use Modules\Core\Http\Responses\ApiErrorResponseFactory;
use Symfony\Component\HttpFoundation\Response;

final class RequireTenantPermissionMiddleware
{
    public function __construct(
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly PermissionCheckerInterface $permissions,
        private readonly ApiErrorResponseFactory $responses,
    ) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $userId = $this->currentUser->currentUserId();
        $tenantId = $this->currentTenant->currentTenantId();

        if (
            $userId === null
            || $tenantId === null
            || ! $this->permissions->allows($userId, $tenantId, $permission)
        ) {
            return $this->responses->make(
                'TENANT_PERMISSION_REQUIRED',
                'You do not have permission to perform this tenant action.',
                Response::HTTP_FORBIDDEN,
                'authorization',
                ['permission' => $permission],
            );
        }

        return $next($request);
    }
}
