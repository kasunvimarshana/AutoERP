<?php
declare(strict_types=1);
namespace Modules\Tenant\Application\Services;

use Modules\Tenant\Application\Contracts\UpdateTenantServiceInterface;
use Modules\Tenant\Application\DTOs\UpdateTenantData;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Domain\Events\TenantUpdated;
use Modules\Tenant\Domain\Exceptions\TenantNotFoundException;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;

class UpdateTenantService implements UpdateTenantServiceInterface
{
    public function __construct(private readonly TenantRepositoryInterface $repo) {}

    public function execute(int $id, UpdateTenantData $data): Tenant
    {
        $tenant = $this->repo->update($id, array_filter($data->toArray(), fn($v) => $v !== null));
        if (!$tenant) {
            throw new TenantNotFoundException($id);
        }
        event(new TenantUpdated($tenant->getId(), $tenant->getId()));
        return $tenant;
    }
}
