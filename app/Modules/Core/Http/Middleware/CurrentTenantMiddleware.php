<?php

declare(strict_types=1);

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Contracts\CurrentTenantContextResolverInterface;
use Modules\Core\Exceptions\CurrentTenantContextResolutionException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class CurrentTenantMiddleware
{
    public function __construct(private readonly CurrentTenantContextResolverInterface $resolver) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $context = $this->resolver->resolve($request);
        } catch (CurrentTenantContextResolutionException $exception) {
            return $this->errorResponse($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($context === null) {
            if ($this->currentTenantRequired()) {
                return $this->errorResponse(
                    'Unable to resolve tenant context for the active request.',
                    Response::HTTP_BAD_REQUEST,
                );
            }

            return $next($request);
        }

        if (! $this->resolver->hasAccess($request, $context)) {
            return $this->errorResponse(
                'Authenticated user cannot access the resolved tenant.',
                Response::HTTP_FORBIDDEN,
            );
        }

        $request->attributes->set($this->configString('request_attribute', 'current_tenant'), $context->toArray());
        $request->attributes->set($this->configString('id_attribute', 'current_tenant_id'), $context->tenantId());
        $request->attributes->set($this->configString('code_attribute', 'current_tenant_code'), $context->tenantCode());
        $request->attributes->set($this->configString('uuid_attribute', 'current_tenant_uuid'), $context->tenantUuid());
        $request->attributes->set(
            $this->configString('isolation_key_attribute', 'current_tenant_isolation_key'),
            $context->isolationKey(),
        );
        $request->attributes->set($this->configString('domain_attribute', 'current_tenant_domain'), $context->domain());
        $request->attributes->set($this->configString('status_attribute', 'current_tenant_status'), $context->status());
        $request->attributes->set($this->configString('active_attribute', 'current_tenant_is_active'), $context->isActive());
        $request->attributes->set(
            $this->configString('application_attribute', 'current_application_id'),
            $context->applicationId(),
        );
        $request->attributes->set($this->configString('source_attribute', 'current_tenant_source'), $context->source());

        return $next($request);
    }

    private function currentTenantRequired(): bool
    {
        if (! function_exists('config')) {
            return true;
        }

        try {
            return (bool) config('core.current_tenant.required', true);
        } catch (Throwable) {
            return true;
        }
    }

    private function configString(string $key, string $fallback): string
    {
        if (! function_exists('config')) {
            return $fallback;
        }

        try {
            return (string) config('core.current_tenant.'.$key, $fallback);
        } catch (Throwable) {
            return $fallback;
        }
    }

    private function errorResponse(string $message, int $status): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], $status);
    }
}
