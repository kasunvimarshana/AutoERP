<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Services\Platform;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;

final class PlatformConfigurationOrganizationTargetService
{
    public function __construct(
        private readonly OrganizationUnitModel $organizationUnits,
        private readonly TenantExecutionContextInterface $executionContext,
    ) {}

    /** @return LengthAwarePaginator<int, OrganizationUnitModel> */
    public function page(int $tenantId, ?string $search, int $page, int $perPage): LengthAwarePaginator
    {
        return $this->executionContext->runForTenant(
            $tenantId,
            function () use ($search, $page, $perPage): LengthAwarePaginator {
                $term = trim((string) $search);

                return $this->organizationUnits->newQuery()
                    ->select(['id', 'tenant_id', 'name', 'code', 'path', 'depth', 'is_active'])
                    ->when($term !== '', static function (Builder $query) use ($term): void {
                        $query->where(static function (Builder $searchQuery) use ($term): void {
                            $searchQuery->where('name', 'like', "%{$term}%")
                                ->orWhere('code', 'like', "%{$term}%")
                                ->orWhere('path', 'like', "%{$term}%");
                        });
                    })
                    ->orderBy('path')
                    ->orderBy('id')
                    ->paginate(min(max($perPage, 1), 50), ['*'], 'page', max($page, 1));
            },
        );
    }

    public function find(int $tenantId, int $organizationUnitId): ?OrganizationUnitModel
    {
        return $this->executionContext->runForTenant(
            $tenantId,
            function () use ($organizationUnitId): ?OrganizationUnitModel {
                $organization = $this->organizationUnits->newQuery()
                    ->select(['id', 'tenant_id', 'name', 'code', 'path', 'depth', 'is_active'])
                    ->find($organizationUnitId);

                return $organization instanceof OrganizationUnitModel ? $organization : null;
            },
        );
    }
}
