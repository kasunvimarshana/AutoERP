<?php

declare(strict_types=1);

namespace Modules\Tenant\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;
use Modules\Tenant\Models\TenantPlanModel;

final class EloquentTenantPlanRepository implements TenantPlanRepositoryInterface
{
    public function __construct(private readonly TenantPlanModel $model) {}

    public function findById(int|string $id): ?DataRecord
    {
        $model = $this->query()->find($id);
        return $model instanceof TenantPlanModel ? $this->record($model) : null;
    }

    public function findBySlug(string $slug): ?DataRecord
    {
        $model = $this->query()->where('slug', strtolower(trim($slug)))->first();
        return $model instanceof TenantPlanModel ? $this->record($model) : null;
    }

    public function create(array $attributes): DataRecord
    {
        return $this->record($this->model->newQuery()->create($attributes));
    }

    public function updateWithVersion(int|string $id, int $expectedVersion, array $attributes): ?DataRecord
    {
        $attributes['row_version'] = $expectedVersion + 1;
        $attributes['updated_at'] = now();
        $updated = $this->model->newQuery()->whereKey($id)->where('row_version', $expectedVersion)->update($attributes);
        return $updated === 1 ? $this->findById($id) : null;
    }

    public function pageByFilters(?bool $isActive, ?string $billingInterval, ?string $search, int $perPage, int $page): PagedResult
    {
        $query = $this->query()
            ->when($isActive !== null, fn (Builder $q) => $q->where('is_active', $isActive))
            ->when($billingInterval !== null && trim($billingInterval) !== '', fn (Builder $q) => $q->where('billing_interval', trim($billingInterval)))
            ->when($search !== null && trim($search) !== '', function (Builder $q) use ($search): void {
                $term = trim((string) $search);
                $q->where(fn (Builder $nested) => $nested->where('name', 'like', "%{$term}%")->orWhere('slug', 'like', "%{$term}%"));
            })->orderBy('price')->orderBy('name');
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        $items = array_values(array_map(fn (Model $model): DataRecord => $this->record($model), $paginator->items()));
        return new PagedResult($items, $paginator->total(), $paginator->currentPage(), $paginator->perPage());
    }

    public function isAssigned(int|string $id): bool
    {
        return $this->model->newQuery()->whereKey($id)->whereHas('tenants')->exists();
    }

    private function query(): Builder
    {
        return $this->model->newQuery()->with('currency:id,code,name,symbol,is_active');
    }

    private function record(TenantPlanModel $model): DataRecord
    {
        $payload = $model->attributesToArray();
        $payload['currency'] = $model->relationLoaded('currency') && $model->currency !== null
            ? $model->currency->only(['id', 'code', 'name', 'symbol', 'is_active']) : null;
        return new DataRecord($payload);
    }
}
