<?php

declare(strict_types=1);

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Core\Contracts\CurrentOrganizationUnitContextResolverInterface;
use Modules\Core\Exceptions\CurrentOrganizationUnitContextResolutionException;
use Modules\Core\Http\Responses\ApiErrorResponseFactory;
use Symfony\Component\HttpFoundation\Response;

final class CurrentOrganizationUnitMiddleware
{
    private const MODE_CONFIGURED = 'configured';
    private const MODE_REQUIRED = 'required';
    private const MODE_OPTIONAL = 'optional';

    public function __construct(
        private readonly CurrentOrganizationUnitContextResolverInterface $resolver,
        private readonly ApiErrorResponseFactory $responses,
    ) {}

    public function handle(Request $request, Closure $next, string $mode = self::MODE_CONFIGURED): Response
    {
        try {
            $context = $this->resolver->resolve($request);
        } catch (CurrentOrganizationUnitContextResolutionException $exception) {
            report($exception);
            return $this->responses->forStatus(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'Organization unit context could not be resolved.',
            );
        }

        if ($context === null) {
            if ($this->isRequired($mode)) {
                return $this->responses->forStatus(
                    Response::HTTP_BAD_REQUEST,
                    'Select an organization unit before performing this action.',
                );
            }
            return $next($request);
        }

        if (! $this->resolver->hasAccess($request, $context)) {
            return $this->responses->forStatus(
                Response::HTTP_FORBIDDEN,
                'Authenticated user cannot access the selected organization unit.',
            );
        }

        $request->attributes->set($this->configString('request_attribute', 'current_organization_unit'), $context);
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

    private function isRequired(string $mode): bool
    {
        return match ($mode) {
            self::MODE_REQUIRED => true,
            self::MODE_OPTIONAL => false,
            self::MODE_CONFIGURED => (bool) config('core.current_organization_unit.required', false),
            default => throw new \InvalidArgumentException('Unsupported organization-unit middleware mode.'),
        };
    }

    private function configString(string $key, string $fallback): string
    {
        return (string) config('core.current_organization_unit.'.$key, $fallback);
    }
}
