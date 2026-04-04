<?php
namespace Modules\Tenant\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Tenant\Application\Contracts\UpdateTenantServiceInterface;
use Modules\Tenant\Application\DTOs\TenantData;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Domain\Events\TenantUpdated;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;

class UpdateTenantService implements UpdateTenantServiceInterface
{
    public function __construct(private readonly TenantRepositoryInterface $repository) {}

    public function execute(Tenant $tenant, TenantData $data): Tenant
    {
        $updated = $this->repository->update($tenant, $data->toArray());
        Event::dispatch(new TenantUpdated($tenant->id, $tenant->id));
        return $updated;
    }
}
