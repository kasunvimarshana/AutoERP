<?php
namespace Modules\Authorization\Application\Contracts;

use Modules\Authorization\Application\DTOs\SyncPermissionsData;

interface SyncRolePermissionsServiceInterface
{
    public function execute(SyncPermissionsData $data): void;
}
