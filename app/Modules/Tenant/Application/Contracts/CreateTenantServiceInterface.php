<?php
declare(strict_types=1);
namespace Modules\Tenant\Application\Contracts;

use Modules\Tenant\Application\DTOs\CreateTenantData;
use Modules\Tenant\Domain\Entities\Tenant;

interface CreateTenantServiceInterface
{
    public function execute(CreateTenantData $data): Tenant;
}
