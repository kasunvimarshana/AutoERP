<?php
namespace Modules\Tenant\Infrastructure\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Modules\Tenant\Domain\Contracts\TenantResolverInterface;
class TenantMiddleware
{
    public function __construct(private TenantResolverInterface $resolver) {}
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolver->resolve($request);
        if ($tenant) {
            app()->instance('current.tenant', $tenant);
            app()->instance('current.tenant.id', $tenant->id);
        }
        return $next($request);
    }
}
