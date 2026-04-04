<?php
namespace Modules\Authorization\Application\Contracts;

use Modules\Authorization\Application\DTOs\PermissionData;
use Modules\Authorization\Domain\Entities\Permission;

interface CreatePermissionServiceInterface
{
    public function execute(PermissionData $data): Permission;
}
