<?php
namespace Modules\Configuration\Application\Contracts;
use Modules\Configuration\Domain\Entities\SystemSetting;

interface SetSettingServiceInterface
{
    public function execute(int $tenantId, string $group, string $key, ?string $value, string $type = 'string'): SystemSetting;
}
