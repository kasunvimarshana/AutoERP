<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Tenant\Application\Contracts\DeleteTenantServiceInterface;
use Modules\Tenant\Domain\Events\TenantDeleted;
use Modules\Tenant\Domain\Exceptions\TenantNotFoundException;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;

class DeleteTenantService extends BaseService implements DeleteTenantServiceInterface
{
    private TenantRepositoryInterface $tenantRepository;

    public function __construct(TenantRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->tenantRepository = $repository;
    }

    protected function handle(array $data): bool
    {
        $id = $data['id'];
        $tenant = $this->tenantRepository->find($id);
        if (! $tenant) {
            throw new TenantNotFoundException($id);
        }
        $deleted = $this->tenantRepository->delete($id);
        if ($deleted) {
            $this->addEvent(new TenantDeleted($id));
        }

        return $deleted;
    }
}
