<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;
use Modules\Core\Repositories\EloquentRepository;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;

final class EloquentOrganizationUnitRepository extends EloquentRepository implements OrganizationUnitRepositoryInterface
{
    public function __construct(OrganizationUnitModel $model)
    {
        parent::__construct($model);
    }

    public function countByTenant(int $tenantId): int
    {
        return $this->query()->where('tenant_id', $tenantId)->whereNull('retired_at')->count();
    }

    public function pageByTenant(int $tenantId, array $criteria, int $perPage, int $page): PagedResult
    {
        $query = $this->baseQuery($tenantId);
        $search = trim((string) ($criteria['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $nested) use ($search): void {
                $nested->where('name', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%')
                    ->orWhere('path', 'like', '%'.$search.'%');
            });
        }
        if (array_key_exists('is_active', $criteria)) {
            $query->where('is_active', (bool) $criteria['is_active']);
        }

        $parentCandidatesFor = is_numeric($criteria['parent_candidates_for'] ?? null)
            ? (int) $criteria['parent_candidates_for']
            : null;
        if ($parentCandidatesFor !== null && $parentCandidatesFor > 0) {
            $target = $this->query()
                ->where('tenant_id', $tenantId)
                ->whereKey($parentCandidatesFor)
                ->whereNull('retired_at')
                ->first(['id', 'path']);
            if ($target instanceof OrganizationUnitModel) {
                $targetPath = (string) $target->getAttribute('path');
                $query->whereKeyNot($parentCandidatesFor)
                    ->where('is_active', true)
                    ->whereNull('retired_at')
                    ->where(function (Builder $candidate) use ($targetPath): void {
                        $candidate->where('path', '!=', $targetPath)
                            ->where('path', 'not like', $this->escapeLike($targetPath).'/%');
                    });
            }
        }

        if (empty($criteria['include_retired']) || $parentCandidatesFor !== null) {
            $query->whereNull('retired_at');
        }

        $paginator = $query->orderBy('path')->paginate($perPage, ['*'], 'page', $page);
        $items = [];
        foreach ($paginator->items() as $model) {
            if ($model instanceof Model) {
                $items[] = $this->toRecord($model);
            }
        }

        return new PagedResult($items, $paginator->total(), $paginator->currentPage(), $paginator->perPage());
    }

    public function listAccessibleByIds(int $tenantId, array $organizationUnitIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map(
            static fn (mixed $id): int => is_numeric($id) ? (int) $id : 0,
            $organizationUnitIds,
        ), static fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            return [];
        }

        return $this->baseQuery($tenantId)
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->whereNull('retired_at')
            ->orderBy('path')
            ->get()
            ->map(fn (Model $model): DataRecord => $this->toRecord($model))
            ->values()
            ->all();
    }

    public function findByTenantAndCode(int $tenantId, string $code): ?DataRecord
    {
        $model = $this->baseQuery($tenantId)
            ->where('code', trim($code))
            ->whereNull('retired_at')
            ->first();

        return $model instanceof Model ? $this->toRecord($model) : null;
    }

    public function lockActiveByTenantAndId(int $tenantId, int $organizationUnitId): ?DataRecord
    {
        $model = $this->baseQuery($tenantId)
            ->whereKey($organizationUnitId)
            ->where('is_active', true)
            ->whereNull('retired_at')
            ->lockForUpdate()
            ->first();

        return $model instanceof Model ? $this->toRecord($model) : null;
    }

    private function baseQuery(int $tenantId): Builder
    {
        return $this->query(['type:id,tenant_id,name,level,is_active', 'parent:id,tenant_id,name,code,path,depth,is_active,retired_at'])
            ->where('tenant_id', $tenantId);
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }

    protected function toRecord(Model $model): DataRecord
    {
        $payload = $model->attributesToArray();
        $payload['type'] = $model->getRelation('type')?->attributesToArray();
        $payload['parent'] = $model->getRelation('parent')?->attributesToArray();
        $payload['has_logo'] = is_string($model->getAttribute('logo_object_key'))
            && trim((string) $model->getAttribute('logo_object_key')) !== '';
        unset($payload['logo_object_key'], $payload['path_hash']);

        return new DataRecord($payload);
    }
}
