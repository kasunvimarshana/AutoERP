<?php

declare(strict_types=1);

namespace Modules\Core\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Application\Contracts\CurrentUserContextResolverInterface;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class CurrentUserMiddleware
{
    public function __construct(private readonly CurrentUserContextResolverInterface $resolver)
    {
    }

    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $context = $this->resolver->resolve($request);

        if ($context === null) {
            if ($this->currentUserRequired()) {
                return $this->errorResponse(
                    'Unable to resolve authenticated user context.',
                    Response::HTTP_UNAUTHORIZED,
                );
            }

            return $next($request);
        }

        $requestedTenantId = $this->resolver->resolveRequestedTenantId($request);
        if ($requestedTenantId !== null && ! $this->resolver->hasTenantAccess($context, $requestedTenantId)) {
            return $this->errorResponse(
                'Authenticated user cannot access the requested tenant.',
                Response::HTTP_FORBIDDEN,
            );
        }

        $request->setUserResolver(static fn () => $context->user());
        $request->attributes->set($this->configString('request_attribute', 'current_user'), $context->toArray());
        $request->attributes->set($this->configString('id_attribute', 'current_user_id'), $context->userIdAsInt());
        $request->attributes->set($this->configString('guard_attribute', 'current_user_guard'), $context->guard());
        $request->attributes->set(
            $this->configString('provider_attribute', 'current_user_provider'),
            $context->provider(),
        );
        $request->attributes->set(
            $this->configString('tenant_attribute', 'current_tenant_id'),
            $requestedTenantId ?? $context->tenantId(),
        );
        $request->attributes->set(
            $this->configString('organization_unit_attribute', 'current_organization_unit_id'),
            $context->organizationUnitId(),
        );
        $request->attributes->set(
            $this->configString('application_attribute', 'current_application_id'),
            $context->applicationId(),
        );

        return $next($request);
    }

    private function currentUserRequired(): bool
    {
        if (! function_exists('config')) {
            return true;
        }

        try {
            return (bool) config('core.current_user.required', true);
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
            return (string) config('core.current_user.' . $key, $fallback);
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
