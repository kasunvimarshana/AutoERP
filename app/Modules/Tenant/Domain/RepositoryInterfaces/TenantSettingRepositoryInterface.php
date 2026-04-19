<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Tenant\Domain\Entities\TenantSetting;

interface TenantSettingRepositoryInterface extends RepositoryInterface
{
    public function findByTenantAndKey(int $tenantId, string $key): ?TenantSetting;

    /**
     * @return iterable<int, TenantSetting>
     */
    public function getByTenant(int $tenantId, ?string $group = null, ?bool $isPublic = null): iterable;

    public function save(TenantSetting $setting): TenantSetting;
}
