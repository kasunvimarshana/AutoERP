<?php
namespace Modules\Tenant\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Tenant\Application\Contracts\CreateTenantServiceInterface;
use Modules\Tenant\Application\DTOs\TenantData;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Domain\Events\TenantCreated;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;

class CreateTenantService implements CreateTenantServiceInterface
{
    public function __construct(private readonly TenantRepositoryInterface $repository) {}

    public function execute(TenantData $data): Tenant
    {
        $tenant = $this->repository->create($data->toArray());
        Event::dispatch(new TenantCreated($tenant->id, $tenant->id));
        return $tenant;
    }
}
