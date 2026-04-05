<?php
declare(strict_types=1);
namespace Modules\Tenant\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Tenant\Application\Contracts\GetTenantServiceInterface;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Domain\Exceptions\TenantNotFoundException;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;

class GetTenantService implements GetTenantServiceInterface
{
    public function __construct(private readonly TenantRepositoryInterface $repo) {}

    public function findById(int $id): Tenant
    {
        $tenant = $this->repo->findById($id);
        if (!$tenant) {
            throw new TenantNotFoundException($id);
        }
        return $tenant;
    }

    public function findAll(int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->repo->findAll($perPage, $page);
    }
}
