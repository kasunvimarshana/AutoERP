<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

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

    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $userId = $this->currentUser->currentUserId();
        $tenantId = $this->currentTenant->currentTenantId();

        if (
            $userId === null
            || $tenantId === null
            || ! $this->allowsAny($userId, $tenantId, $permissions)
        ) {
            return $this->responses->make(
                'TENANT_PERMISSION_REQUIRED',
                'You do not have permission to perform this tenant action.',
                Response::HTTP_FORBIDDEN,
                'authorization',
                [
                    'permission' => $permissions[0] ?? null,
                    'permissions' => $permissions,
                ],
            );
        }

        return $next($request);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function allowsAny(int $userId, int $tenantId, array $permissions): bool
    {
        if ($permissions === []) {
            return false;
        }

        foreach ($permissions as $permission) {
            if ($this->permissions->allows($userId, $tenantId, $permission)) {
                return true;
            }
        }

        return false;
    }
}
