<?php

declare(strict_types=1);

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\CurrentTenantContextResolverInterface;
use Modules\Core\Http\Responses\ApiErrorResponseFactory;
use Symfony\Component\HttpFoundation\Response;

/** Enforces access to tenant context that was already resolved for the request. */
final class RequireCurrentTenantAccessMiddleware
{
    public function __construct(
        private readonly CurrentTenantContextResolverInterface $access,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly ApiErrorResponseFactory $responses,
    ) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $context = $this->currentTenant->requireCurrent();
        if (! $this->access->hasAccess($request, $context)) {
            return $this->responses->forStatus(
                Response::HTTP_FORBIDDEN,
                'Authenticated user cannot access the resolved tenant.',
            );
        }

        return $next($request);
    }
}
