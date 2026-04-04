<?php
namespace Modules\Tenant\Application\Contracts;

use Modules\Tenant\Domain\Entities\Tenant;

interface DeleteTenantServiceInterface
{
    public function execute(Tenant $tenant): bool;
}
