<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Tenant\Domain\Entities\TenantSetting;

interface FindTenantSettingsServiceInterface
{
    public function find(int $id): ?TenantSetting;

    public function findByTenantAndKey(int $tenantId, string $key): ?TenantSetting;

    public function listByTenant(int $tenantId, ?string $group = null, ?bool $isPublic = null): Collection;

    public function paginateByTenant(int $tenantId, ?string $group, ?bool $isPublic, int $perPage, int $page): LengthAwarePaginator;
}
