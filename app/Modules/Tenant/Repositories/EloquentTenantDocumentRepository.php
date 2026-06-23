<?php

declare(strict_types=1);

namespace Modules\Tenant\Repositories;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\DTOs\DataRecord;
use Modules\Tenant\Models\TenantDocumentModel;

final class EloquentTenantDocumentRepository implements TenantDocumentRepositoryInterface
{
    public function __construct(private readonly TenantDocumentModel $model) {}

    public function listByTenant(int $tenantId): array
    {
        return $this->model->newQuery()->where('tenant_id', $tenantId)->orderBy('name')->get()
            ->map(fn (Model $model): DataRecord => $this->record($model))->values()->all();
    }

    public function findByIdForTenant(int|string $id, int $tenantId): ?DataRecord
    {
        $model = $this->model->newQuery()->where('tenant_id', $tenantId)->find($id);
        return $model instanceof TenantDocumentModel ? $this->record($model) : null;
    }

    public function findByTenantAndName(int $tenantId, string $name): ?DataRecord
    {
        $model = $this->model->newQuery()->where('tenant_id', $tenantId)->where('name', trim($name))->first();
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

    public function updateWithVersion(int|string $id, int $tenantId, int $expectedVersion, array $attributes): ?DataRecord
    {
        $attributes['row_version'] = $expectedVersion + 1;
        $attributes['updated_at'] = now();
        $updated = $this->model->newQuery()->whereKey($id)->where('tenant_id', $tenantId)
            ->where('row_version', $expectedVersion)->update($attributes);
        return $updated === 1 ? $this->findByIdForTenant($id, $tenantId) : null;
    }

    public function deleteWithVersion(int|string $id, int $tenantId, int $expectedVersion): bool
    {
        return $this->model->newQuery()->whereKey($id)->where('tenant_id', $tenantId)
            ->where('row_version', $expectedVersion)->delete() === 1;
    }

    private function record(Model $model): DataRecord
    {
        return new DataRecord($model->attributesToArray());
    }
}
