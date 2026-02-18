<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Core\Services\TenantContext;
use Symfony\Component\HttpFoundation\Response;

class RequireTenant
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->tenantContext->hasTenant()) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant context is required',
            ], 400);
        }

        return $next($request);
    }
}
