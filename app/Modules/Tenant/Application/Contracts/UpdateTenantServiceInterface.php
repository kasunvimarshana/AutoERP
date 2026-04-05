<?php
declare(strict_types=1);
namespace Modules\Tenant\Application\Contracts;

use Modules\Tenant\Application\DTOs\UpdateTenantData;
use Modules\Tenant\Domain\Entities\Tenant;

interface UpdateTenantServiceInterface
{
    public function execute(int $id, UpdateTenantData $data): Tenant;
}
