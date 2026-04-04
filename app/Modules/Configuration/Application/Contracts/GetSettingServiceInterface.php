<?php
namespace Modules\Configuration\Application\Contracts;
use Modules\Configuration\Domain\Entities\SystemSetting;

interface GetSettingServiceInterface
{
    public function execute(int $tenantId, string $group, string $key): ?SystemSetting;
}
