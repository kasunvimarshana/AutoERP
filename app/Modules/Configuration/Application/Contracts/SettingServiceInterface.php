<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Contracts;

use Modules\Configuration\Domain\Entities\Setting;

interface SettingServiceInterface
{
    public function get(string $tenantId, string $key): mixed;

    public function set(string $tenantId, string $key, mixed $value): void;

    public function getSetting(string $tenantId, string $id): Setting;

    public function createSetting(string $tenantId, array $data): Setting;

    public function updateSetting(string $tenantId, string $id, array $data): Setting;

    public function deleteSetting(string $tenantId, string $id): void;

    /** @return Setting[] */
    public function getAllSettings(string $tenantId): array;

    /** @return Setting[] */
    public function getSettingsByGroup(string $tenantId, string $group): array;
}
