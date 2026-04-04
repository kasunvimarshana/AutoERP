<?php
namespace Modules\Configuration\Application\Services;

use Modules\Configuration\Application\Contracts\SetSettingServiceInterface;
use Modules\Configuration\Domain\Entities\SystemSetting;
use Modules\Configuration\Domain\Events\SystemSettingChanged;
use Modules\Configuration\Domain\RepositoryInterfaces\SystemSettingRepositoryInterface;

class SetSettingService implements SetSettingServiceInterface
{
    public function __construct(private readonly SystemSettingRepositoryInterface $repository) {}

    public function execute(int $tenantId, string $group, string $key, ?string $value, string $type = 'string'): SystemSetting
    {
        $setting = $this->repository->upsert($tenantId, $group, $key, $value, $type);
        event(new SystemSettingChanged($tenantId, "{$group}.{$key}"));
        return $setting;
    }
}
