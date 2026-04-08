<?php

declare(strict_types=1);

namespace Modules\Settings\Domain\RepositoryInterfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Settings\Domain\Entities\Setting;

interface SettingRepositoryInterface extends RepositoryInterface
{
    public function save(Setting $setting): Setting;

    public function findByKey(int $tenantId, string $groupKey, string $settingKey): ?Setting;

    public function findByGroup(int $tenantId, string $groupKey): Collection;

    public function list(array $filters = [], int $perPage = 15, int $page = 1): LengthAwarePaginator;

    public function delete(int $id): bool;
}
