<?php

declare(strict_types=1);

namespace Modules\Configuration\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Configuration\Models\ConfigurationModel;
use Modules\Configuration\Models\TenantConfigurationModel;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;
use Modules\Core\Repositories\EloquentRepository;

final class EloquentConfigurationRepository extends EloquentRepository implements ConfigurationRepositoryInterface
{
    public function __construct(
        ConfigurationModel $model,
        private readonly TenantConfigurationModel $tenantModel,
    ) {
        parent::__construct($model, ConfigurationModel::COLUMN_ID);
    }

    public function findByKey(string $key): ?DataRecord
    {
        $model = $this->query()->where(ConfigurationModel::COLUMN_KEY, $key)->first();

        if (! $model instanceof Model) {
            return null;
        }

        return $this->toRecord($model);
    }

    public function findByTenantAndKey(int $tenantId, string $key): ?DataRecord
    {
        $model = $this->tenantQuery()
            ->where(TenantConfigurationModel::COLUMN_TENANT_ID, $tenantId)
            ->where(TenantConfigurationModel::COLUMN_KEY, $key)
            ->first();

        if (! $model instanceof Model) {
            return null;
        }

        return $this->toRecord($model);
    }

    public function findResolvedByScope(string $key, ?int $tenantId = null): ?DataRecord
    {
        if ($tenantId !== null) {
            $tenantRecord = $this->findByTenantAndKey($tenantId, $key);
            if ($tenantRecord instanceof DataRecord) {
                return $tenantRecord;
            }
        }

        return $this->findByKey($key);
    }

    public function pageByFilters(
        ?string $prefix,
        ?string $source,
        int $perPage,
        int $page,
        ?string $scope = null,
        ?int $tenantId = null,
    ): PagedResult {
        $resolvedPage = $page > 0 ? $page : 1;
        $resolvedPerPage = $perPage > 0 ? $perPage : 1;

        $resolvedScope = $scope ?? ConfigurationScope::GLOBAL;
        $query = $resolvedScope === ConfigurationScope::TENANT && $tenantId !== null
            ? $this->tenantQuery()->where(TenantConfigurationModel::COLUMN_TENANT_ID, $tenantId)
            : $this->query();

        $paginator = $this->applyFilters($query, $prefix, $source)
            ->paginate($resolvedPerPage, ['*'], 'page', $resolvedPage);

        $items = [];
        foreach ($paginator->items() as $model) {
            if ($model instanceof Model) {
                $items[] = $this->toRecord($model);
            }
        }

        return new PagedResult(
            $items,
            $paginator->total(),
            $paginator->currentPage(),
            $paginator->perPage(),
        );
    }

    public function upsertScoped(string $key, array $attributes, ?int $tenantId = null): DataRecord
    {
        if ($tenantId === null) {
            $current = $this->findByKey($key);

            if ($current instanceof DataRecord) {
                return $this->update($current->id(), $attributes);
            }

            return $this->create($attributes);
        }

        $query = $this->tenantQuery()
            ->where(TenantConfigurationModel::COLUMN_TENANT_ID, $tenantId)
            ->where(TenantConfigurationModel::COLUMN_KEY, $key);

        $existing = $query->first();
        if ($existing instanceof Model) {
            $existing->fill($attributes);
            $existing->save();

            return $this->toRecord($existing);
        }

        $created = $this->tenantQuery()->create($attributes);

        return $this->toRecord($created);
    }

    public function deleteScopedByKey(string $key, ?int $tenantId = null): bool
    {
        $query = $tenantId === null
            ? $this->query()->where(ConfigurationModel::COLUMN_KEY, $key)
            : $this->tenantQuery()
                ->where(TenantConfigurationModel::COLUMN_TENANT_ID, $tenantId)
                ->where(TenantConfigurationModel::COLUMN_KEY, $key);

        $model = $query->first();

        if (! $model instanceof Model) {
            return false;
        }

        return (bool) $model->delete();
    }

    protected function toRecord(Model $model): DataRecord
    {
        /** @var array<string, mixed> $payload */
        $payload = $model->attributesToArray();

        if (($payload[TenantConfigurationModel::COLUMN_TENANT_ID] ?? null) !== null) {
            $payload['scope'] = ConfigurationScope::TENANT;
        } else {
            $payload['scope'] = ConfigurationScope::GLOBAL;
        }

        return new DataRecord($payload);
    }

    private function applyFilters(Builder $query, ?string $prefix, ?string $source): Builder
    {
        if ($prefix !== null && $prefix !== '') {
            $query->where(ConfigurationModel::COLUMN_KEY, 'like', $prefix.'%');
        }

        if ($source !== null && $source !== '') {
            $query->where(ConfigurationModel::COLUMN_SOURCE, $source);
        }

        return $query;
    }

    private function tenantQuery(): Builder
    {
        return $this->tenantModel->newQuery();
    }
}
