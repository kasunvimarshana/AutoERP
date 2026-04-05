<?php
declare(strict_types=1);
namespace Modules\Configuration\Application\Services;

use Modules\Configuration\Application\Contracts\SettingServiceInterface;
use Modules\Configuration\Domain\Entities\Setting;
use Modules\Configuration\Domain\Exceptions\SettingNotFoundException;
use Modules\Configuration\Domain\RepositoryInterfaces\SettingRepositoryInterface;

class SettingService implements SettingServiceInterface
{
    public function __construct(private readonly SettingRepositoryInterface $repo) {}

    public function get(int $tenantId, string $key): mixed
    {
        $setting = $this->repo->findByKey($tenantId, $key);
        if (!$setting) {
            throw new SettingNotFoundException($key);
        }
        return $setting->getValue();
    }

    public function set(int $tenantId, string $key, mixed $value, string $type = 'string', ?string $description = null): Setting
    {
        return $this->repo->set($tenantId, $key, $value, $type, $description);
    }

    public function getAll(int $tenantId): array
    {
        return $this->repo->findByTenant($tenantId);
    }

    public function delete(int $tenantId, string $key): bool
    {
        return $this->repo->delete($tenantId, $key);
    }
}
