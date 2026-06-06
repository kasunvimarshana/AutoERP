<?php

declare(strict_types=1);

namespace Modules\User\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;
use Modules\Core\Repositories\EloquentRepository;
use Modules\User\Models\RoleModel;

final class EloquentRoleRepository extends EloquentRepository implements RoleRepositoryInterface
{
    public function __construct(RoleModel $model)
    {
        parent::__construct($model);
    }

    public function findByTenantNameGuard(?int $tenantId, string $name, string $guardName, ?int $excludeId = null): ?DataRecord
    {
        $query = $this->query()
            ->where('name', trim($name))
            ->where('guard_name', trim($guardName));

        $this->applyTenantScope($query, $tenantId);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        $model = $query->first();

        return $model instanceof Model ? $this->toRecord($model) : null;
    }

    public function pageByFilters(?int $tenantId, ?string $search, int $perPage, int $page): PagedResult
    {
        $query = $this->query();
        $this->applyTenantScope($query, $tenantId);

        if ($search !== null && trim($search) !== '') {
            $term = trim($search);
            $query->where(function (Builder $builder) use ($term): void {
                $builder->where('name', 'like', '%'.$term.'%')
                    ->orWhere('description', 'like', '%'.$term.'%')
                    ->orWhere('guard_name', 'like', '%'.$term.'%');
            });
        }

        $paginator = $query->paginate(max(1, $perPage), ['*'], 'page', max(1, $page));

        $items = [];
        foreach ($paginator->items() as $model) {
            if ($model instanceof Model) {
                $items[] = $this->toRecord($model);
            }
        }

        return new PagedResult($items, $paginator->total(), $paginator->currentPage(), $paginator->perPage());
    }

    private function applyTenantScope(Builder $query, ?int $tenantId): void
    {
        if ($tenantId === null) {
            $query->whereNull('tenant_id');

            return;
        }

        $query->where('tenant_id', $tenantId);
    }
}
