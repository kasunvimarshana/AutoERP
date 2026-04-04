<?php
namespace Modules\Configuration\Domain\RepositoryInterfaces;
use Modules\Configuration\Domain\Entities\SystemSetting;

interface SystemSettingRepositoryInterface
{
    public function findByKey(int $tenantId, string $group, string $key): ?SystemSetting;
    public function findByGroup(int $tenantId, string $group): array;
    public function upsert(int $tenantId, string $group, string $key, ?string $value, string $type): SystemSetting;
    public function create(array $data): SystemSetting;
    public function update(SystemSetting $setting, array $data): SystemSetting;
}
