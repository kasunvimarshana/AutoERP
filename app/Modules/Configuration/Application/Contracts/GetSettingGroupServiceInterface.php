<?php
namespace Modules\Configuration\Application\Contracts;

interface GetSettingGroupServiceInterface
{
    public function execute(int $tenantId, string $group): array;
}
