<?php
namespace Modules\Tenant\Application\Contracts;

use Modules\Tenant\Application\DTOs\TenantData;
use Modules\Tenant\Domain\Entities\Tenant;

interface UpdateTenantServiceInterface
{
    public function execute(Tenant $tenant, TenantData $data): Tenant;
}
