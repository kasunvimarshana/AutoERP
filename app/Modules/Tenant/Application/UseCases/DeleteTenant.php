<?php

namespace Modules\Tenant\Application\UseCases;

use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;
use Modules\Tenant\Domain\Events\TenantDeleted;

class DeleteTenant
{
    public function __construct(
        private TenantRepositoryInterface $tenantRepo
    ) {}

    public function execute(int $id): bool
    {
        $deleted = $this->tenantRepo->delete($id);
        if ($deleted) {
            event(new TenantDeleted($id));
        }
        return $deleted;
    }
}
