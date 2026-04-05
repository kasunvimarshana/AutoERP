<?php
declare(strict_types=1);
namespace Modules\Tenant\Application\Services;

use Modules\Tenant\Application\Contracts\DeleteTenantServiceInterface;
use Modules\Tenant\Domain\Exceptions\TenantNotFoundException;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;

class DeleteTenantService implements DeleteTenantServiceInterface
{
    public function __construct(private readonly TenantRepositoryInterface $repo) {}

    public function execute(int $id): bool
    {
        $tenant = $this->repo->findById($id);
        if (!$tenant) {
            throw new TenantNotFoundException($id);
        }
        return $this->repo->delete($id);
    }
}
