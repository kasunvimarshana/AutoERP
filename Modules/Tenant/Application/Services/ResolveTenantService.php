<?php
namespace Modules\Tenant\Application\Services;
use Illuminate\Http\Request;
use Modules\Tenant\Domain\Contracts\TenantRepositoryInterface;
use Modules\Tenant\Domain\Contracts\TenantResolverInterface;
class ResolveTenantService implements TenantResolverInterface
{
    public function __construct(private TenantRepositoryInterface $repo) {}
    public function resolve(Request $request): ?object
    {
        if ($header = $request->header('X-Tenant-ID')) {
            return $this->repo->findById($header);
        }
        $host = $request->getHost();
        $parts = explode('.', $host);
        if (count($parts) >= 3) {
            return $this->repo->findBySlug($parts[0]);
        }
        return $this->repo->findByDomain($host);
    }
}
