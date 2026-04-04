<?php
namespace Modules\Tenant\Application\Contracts;

use Modules\Tenant\Application\DTOs\TenantData;
use Modules\Tenant\Domain\Entities\Tenant;

interface CreateTenantServiceInterface
{
    public function execute(TenantData $data): Tenant;
}
