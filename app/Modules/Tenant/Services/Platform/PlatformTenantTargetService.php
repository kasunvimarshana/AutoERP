<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Platform;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Tenant\Models\TenantModel;

final class PlatformTenantTargetService
{
    public function __construct(private readonly TenantModel $tenants) {}

    /** @return LengthAwarePaginator<int, TenantModel> */
    public function page(?string $search, int $page, int $perPage): LengthAwarePaginator
    {
        $term = trim((string) $search);

        return $this->tenants->newQuery()
            ->select(['id', 'name', 'code', 'status'])
            ->when($term !== '', static function (Builder $query) use ($term): void {
                $query->where(static function (Builder $searchQuery) use ($term): void {
                    $searchQuery->where('name', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%");
                });
            })
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(min(max($perPage, 1), 50), ['*'], 'page', max($page, 1));
    }

    public function find(int $tenantId): ?TenantModel
    {
        $tenant = $this->tenants->newQuery()
            ->select(['id', 'name', 'code', 'status'])
            ->find($tenantId);

        return $tenant instanceof TenantModel ? $tenant : null;
    }
}
