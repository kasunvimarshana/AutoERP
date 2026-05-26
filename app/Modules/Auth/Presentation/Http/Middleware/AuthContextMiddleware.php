<?php

declare(strict_types=1);

namespace Modules\Auth\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Core\Application\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentUserContextAccessorInterface;
use Symfony\Component\HttpFoundation\Response;

final class AuthContextMiddleware
{
    public function __construct(
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('auth_context_user_id', $this->currentUser->currentUserId());
        $request->attributes->set('auth_context_tenant_id', $this->currentTenant->currentTenantId());
        $request->attributes->set(
            'auth_context_organization_unit_id',
            $this->currentOrganizationUnit->currentOrganizationUnitId(),
        );

        return $next($request);
    }
}
