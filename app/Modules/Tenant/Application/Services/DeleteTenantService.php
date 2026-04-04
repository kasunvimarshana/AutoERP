<?php
namespace Modules\Tenant\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Tenant\Application\Contracts\DeleteTenantServiceInterface;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Domain\Events\TenantDeleted;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;

class DeleteTenantService implements DeleteTenantServiceInterface
{
    public function __construct(private readonly TenantRepositoryInterface $repository) {}

    public function execute(Tenant $tenant): bool
    {
        $id = $tenant->id;
        $result = $this->repository->delete($tenant);
        if ($result) {
            Event::dispatch(new TenantDeleted($id, $id));
        }
        return $result;
    }
}
