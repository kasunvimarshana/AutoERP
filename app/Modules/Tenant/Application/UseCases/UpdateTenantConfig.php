<?php

namespace Modules\Tenant\Application\UseCases;

use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;
use Modules\Tenant\Application\DTOs\TenantConfigData;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Domain\Events\TenantConfigChanged;
use Modules\Tenant\Domain\Exceptions\TenantNotFoundException;

class UpdateTenantConfig
{
    public function __construct(
        private TenantRepositoryInterface $tenantRepo
    ) {}

    public function execute(int $id, TenantConfigData $data): Tenant
    {
        $tenant = $this->tenantRepo->find($id);
        if (!$tenant) {
            throw new TenantNotFoundException($id);
        }

        // Update only provided configs
        $tenant->updateConfig($data->toArray());

        $saved = $this->tenantRepo->save($tenant);
        event(new TenantConfigChanged($saved));

        return $saved;
    }
}
