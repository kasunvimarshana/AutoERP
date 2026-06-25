<?php

declare(strict_types=1);

namespace Modules\Tenant\Repositories;

use Modules\Core\Contracts\ClockInterface;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;
use Modules\Tenant\Models\TenantDocumentModel;

final class EloquentTenantDocumentRepository implements TenantDocumentRepositoryInterface
{
    public function __construct(
        private readonly TenantDocumentModel $model,
        private readonly ClockInterface $clock,
    ) {}

    public function pageByTenant(
        int $tenantId,
        ?string $documentType,
        ?string $search,
        int $perPage,
        int $page,
    ): PagedResult {
        $query = $this->model->newQuery()->where('tenant_id', $tenantId);

        if ($documentType !== null && trim($documentType) !== '') {
            $query->where('document_type', trim($documentType));
        }

        if ($search !== null && trim($search) !== '') {
            $term = '%'.trim($search).'%';
            $query->where(function ($builder) use ($term): void {
                $builder->where('name', 'like', $term)
                    ->orWhere('original_filename', 'like', $term);
            });
        }

        $paginator = $query
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(max(1, min($perPage, 100)), ['*'], 'page', max(1, $page));

        return new PagedResult(
            $paginator->getCollection()
                ->map(fn (Model $model): DataRecord => $this->record($model))
                ->values()
                ->all(),
            $paginator->total(),
            $paginator->currentPage(),
            $paginator->perPage(),
        );
    }

    public function findByIdForTenant(int|string $id, int $tenantId): ?DataRecord
    {
        $model = $this->model->newQuery()->where('tenant_id', $tenantId)->find($id);

        return $model instanceof TenantDocumentModel ? $this->record($model) : null;
    }

    public function findByTenantAndName(int $tenantId, string $name): ?DataRecord
    {
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('name', trim($name))
            ->first();

        return $model instanceof TenantDocumentModel ? $this->record($model) : null;
    }

    public function totalSizeByTenant(int $tenantId): int
    {
        return (int) $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->sum('size_bytes');
    }

    public function create(array $attributes): DataRecord
    {
        return $this->record($this->model->newQuery()->create($attributes));
    }

    public function updateWithVersion(
        int|string $id,
        int $tenantId,
        int $expectedVersion,
        array $attributes,
    ): ?DataRecord {
        $attributes['row_version'] = $expectedVersion + 1;
        $attributes['updated_at'] = $this->clock->now();
        $updated = $this->model->newQuery()
            ->whereKey($id)
            ->where('tenant_id', $tenantId)
            ->where('row_version', $expectedVersion)
            ->update($attributes);

        return $updated === 1 ? $this->findByIdForTenant($id, $tenantId) : null;
    }

    public function deleteWithVersion(int|string $id, int $tenantId, int $expectedVersion): bool
    {
        return $this->model->newQuery()
            ->whereKey($id)
            ->where('tenant_id', $tenantId)
            ->where('row_version', $expectedVersion)
            ->delete() === 1;
    }

    private function record(Model $model): DataRecord
    {
        return new DataRecord($model->attributesToArray());
    }
}
