<?php

declare(strict_types=1);

namespace Modules\Tenant\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Core\DTOs\DataRecord;
use Modules\Tenant\Models\TenantDomainModel;
use RuntimeException;

final class EloquentTenantDomainRepository implements TenantDomainRepositoryInterface
{
    private const VERSION_CONFLICT = 'tenant-domain-version-conflict';

    public function __construct(private readonly TenantDomainModel $model) {}

    public function listByTenant(int $tenantId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('is_primary')
            ->orderBy('domain')
            ->get()
            ->map(fn (Model $model): DataRecord => $this->record($model))
            ->values()
            ->all();
    }

    public function findByIdForTenant(int|string $id, int $tenantId): ?DataRecord
    {
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model instanceof TenantDomainModel ? $this->record($model) : null;
    }

    public function findByDomain(string $domain): ?DataRecord
    {
        $model = $this->model->newQuery()
            ->where('domain', strtolower(trim($domain)))
            ->first();

        return $model instanceof TenantDomainModel ? $this->record($model) : null;
    }

    public function findPrimaryByTenant(int $tenantId): ?DataRecord
    {
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('is_primary', true)
            ->where('status', 'active')
            ->whereNotNull('verified_at')
            ->first();

        return $model instanceof TenantDomainModel ? $this->record($model) : null;
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
        $attributes['updated_at'] = now();

        $updated = $this->model->newQuery()
            ->whereKey($id)
            ->where('tenant_id', $tenantId)
            ->where('row_version', $expectedVersion)
            ->update($attributes);

        return $updated === 1 ? $this->findByIdForTenant($id, $tenantId) : null;
    }

    public function setPrimaryWithVersion(
        int|string $id,
        int $tenantId,
        int $expectedVersion,
        ?int $updatedBy,
    ): ?DataRecord {
        try {
            DB::transaction(function () use ($id, $tenantId, $expectedVersion, $updatedBy): void {
                $target = $this->model->newQuery()
                    ->whereKey($id)
                    ->where('tenant_id', $tenantId)
                    ->where('row_version', $expectedVersion)
                    ->lockForUpdate()
                    ->first();

                if (! $target instanceof TenantDomainModel) {
                    throw new RuntimeException(self::VERSION_CONFLICT);
                }

                $this->model->newQuery()
                    ->where('tenant_id', $tenantId)
                    ->where('is_primary', true)
                    ->where('id', '!=', $target->getKey())
                    ->update([
                        'is_primary' => false,
                        'primary_marker' => null,
                        'row_version' => DB::raw('row_version + 1'),
                        'updated_by' => $updatedBy,
                        'updated_at' => now(),
                    ]);

                $updated = $this->model->newQuery()
                    ->whereKey($id)
                    ->where('tenant_id', $tenantId)
                    ->where('row_version', $expectedVersion)
                    ->update([
                        'is_primary' => true,
                        'primary_marker' => 'primary',
                        'row_version' => $expectedVersion + 1,
                        'updated_by' => $updatedBy,
                        'updated_at' => now(),
                    ]);

                if ($updated !== 1) {
                    throw new RuntimeException(self::VERSION_CONFLICT);
                }
            }, 3);
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === self::VERSION_CONFLICT) {
                return null;
            }

            throw $exception;
        }

        return $this->findByIdForTenant($id, $tenantId);
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
