<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ResolveTenant
{
    public function __construct(
        protected TenantConfigClientInterface $client,
        protected TenantConfigManagerInterface $manager
    ) {}

    public function handle(Request $request, Closure $next)
    {
        // Resolve tenant ID
        $tenantId = $request->header('X-Tenant-ID') ?? optional($request->user())->tenant_id;
        if (! $tenantId) {
            throw new BadRequestHttpException('Tenant ID is required.');
        }

        // Validate that the tenant ID is a positive integer to prevent injection attacks
        $tenantId = filter_var($tenantId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($tenantId === false) {
            throw new BadRequestHttpException('Tenant ID must be a valid positive integer.');
        }

        // Attach tenant ID to request for later use
        $request->merge(['tenant_id' => $tenantId]);
        app()->instance('current_tenant_id', $tenantId);

        // Fetch tenant configuration (cached)
        $config = $this->client->getConfig($tenantId);
        // if (! $config) {
        //     throw new BadRequestHttpException('Invalid tenant ID.');
        // }

        if ($config) {
            // Apply configuration to Laravel
            $this->manager->apply($config);
        }

        // Bind tenant config to container for later access
        app()->instance('tenant.config', $config);

        return $next($request);
    }
}
