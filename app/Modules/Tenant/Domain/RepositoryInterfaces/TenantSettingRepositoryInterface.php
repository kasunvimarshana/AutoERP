<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Tenant\Domain\Entities\TenantSetting;

interface TenantSettingRepositoryInterface extends RepositoryInterface
{
    public function findByTenantAndKey(int $tenantId, string $key): ?TenantSetting;

    public function getByTenant(int $tenantId, ?string $group = null, ?bool $isPublic = null): Collection;

    public function save(TenantSetting $setting): TenantSetting;
}
