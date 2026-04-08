<?php

declare(strict_types=1);

namespace Modules\Configuration\Domain\RepositoryInterfaces;

use Modules\Configuration\Domain\Entities\Setting;

interface SettingRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?Setting;

    public function findByKey(string $tenantId, string $key): ?Setting;

    /** @return Setting[] */
    public function findAll(string $tenantId): array;

    /** @return Setting[] */
    public function findByGroup(string $tenantId, string $group): array;

    public function save(Setting $setting): void;

    public function delete(string $tenantId, string $id): void;
}
