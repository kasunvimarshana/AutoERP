<?php

declare(strict_types=1);

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Contracts\CurrentOrganizationUnitContextResolverInterface;
use Modules\Core\Exceptions\CurrentOrganizationUnitContextResolutionException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class CurrentOrganizationUnitMiddleware
{
    public function __construct(private readonly CurrentOrganizationUnitContextResolverInterface $resolver) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $context = $this->resolver->resolve($request);
        } catch (CurrentOrganizationUnitContextResolutionException $exception) {
            return $this->errorResponse($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($context === null) {
            if ($this->required()) {
                return $this->errorResponse(
                    'Unable to resolve organization unit context for the active request.',
                    Response::HTTP_BAD_REQUEST,
                );
            }

            return $next($request);
        }

        if (! $this->resolver->hasAccess($request, $context)) {
            return $this->errorResponse(
                'Authenticated user cannot access the resolved organization unit.',
                Response::HTTP_FORBIDDEN,
            );
        }

        $request->attributes->set($this->configString('request_attribute', 'current_organization_unit'), $context->toArray());
        $request->attributes->set($this->configString('id_attribute', 'current_organization_unit_id'), $context->organizationUnitId());
        $request->attributes->set($this->configString('tenant_id_attribute', 'current_organization_unit_tenant_id'), $context->tenantId());
        $request->attributes->set($this->configString('code_attribute', 'current_organization_unit_code'), $context->code());
        $request->attributes->set($this->configString('path_attribute', 'current_organization_unit_path'), $context->path());
        $request->attributes->set($this->configString('name_attribute', 'current_organization_unit_name'), $context->name());
        $request->attributes->set($this->configString('active_attribute', 'current_organization_unit_is_active'), $context->isActive());
        $request->attributes->set($this->configString('application_attribute', 'current_application_id'), $context->applicationId());
        $request->attributes->set($this->configString('source_attribute', 'current_organization_unit_source'), $context->source());

        return $next($request);
    }

    private function required(): bool
    {
        if (! function_exists('config')) {
            return true;
        }

        try {
            return (bool) config('core.current_organization_unit.required', true);
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
            return (string) config('core.current_organization_unit.'.$key, $fallback);
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
