<?php

namespace Modules\Tenant\Application\UseCases;

use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;
use Modules\Tenant\Application\DTOs\TenantData;
use Modules\Tenant\Domain\Events\TenantUpdated;

class UpdateTenant
{
    public function __construct(
        private TenantRepositoryInterface $tenantRepo
    ) {}

    public function execute(int $id, TenantData $data): Tenant
    {
        $tenant = $this->tenantRepo->find($id);
        if (!$tenant) {
            throw new \RuntimeException('Tenant not found');
        }

        // Update properties (simplified)
        // In a real implementation, use setters or a dedicated update method
        $tenant->updateFromData($data); // you'd implement this method

        $saved = $this->tenantRepo->save($tenant);
        event(new TenantUpdated($saved));
        return $saved;
    }
}
