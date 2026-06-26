<?php

declare(strict_types=1);

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Core\Contracts\CurrentTenantContextResolverInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\Exceptions\CurrentTenantContextResolutionException;
use Modules\Core\Http\Responses\ApiErrorResponseFactory;
use Symfony\Component\HttpFoundation\Response;

/** Resolves and binds tenant context without requiring an authenticated user. */
final class ResolveCurrentTenantMiddleware
{
    public function __construct(
        private readonly CurrentTenantContextResolverInterface $resolver,
        private readonly ApiErrorResponseFactory $responses,
        private readonly TenantExecutionContextInterface $executionContext,
    ) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $context = $this->resolver->resolve($request);
        } catch (CurrentTenantContextResolutionException $exception) {
            report($exception);

            return $this->responses->forStatus(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'Tenant context could not be resolved.',
            );
        }

        if ($context === null) {
            return $this->currentTenantRequired()
                ? $this->responses->forStatus(
                    Response::HTTP_BAD_REQUEST,
                    'Unable to resolve tenant context for the active request.',
                )
                : $next($request);
        }

        $request->attributes->set($this->configString('request_attribute', 'current_tenant'), $context);
        $request->attributes->set($this->configString('id_attribute', 'current_tenant_id'), $context->tenantId());
        $request->attributes->set($this->configString('code_attribute', 'current_tenant_code'), $context->tenantCode());
        $request->attributes->set($this->configString('uuid_attribute', 'current_tenant_uuid'), $context->tenantUuid());
        $request->attributes->set($this->configString('domain_attribute', 'current_tenant_domain'), $context->domain());
        $request->attributes->set($this->configString('status_attribute', 'current_tenant_status'), $context->status());
        $request->attributes->set($this->configString('active_attribute', 'current_tenant_is_active'), $context->isActive());
        $request->attributes->set($this->configString('application_attribute', 'current_application_id'), $context->applicationId());
        $request->attributes->set($this->configString('source_attribute', 'current_tenant_source'), $context->source());

        return $this->executionContext->runForTenant(
            $context->tenantId(),
            static fn (): Response => $next($request),
        );
    }

    private function currentTenantRequired(): bool
    {
        return (bool) config('core.current_tenant.required', true);
    }

    private function configString(string $key, string $fallback): string
    {
        return (string) config('core.current_tenant.'.$key, $fallback);
    }
}
