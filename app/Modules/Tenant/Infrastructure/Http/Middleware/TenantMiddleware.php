<?php
declare(strict_types=1);
namespace Modules\Tenant\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->header('X-Tenant-ID');
        if ($tenantId) {
            $request->attributes->set('tenant_id', (int) $tenantId);
        }
        return $next($request);
    }
}
