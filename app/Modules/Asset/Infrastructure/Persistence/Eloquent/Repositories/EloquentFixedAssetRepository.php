<?php
declare(strict_types=1);
namespace Modules\Asset\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Asset\Domain\Entities\FixedAsset;
use Modules\Asset\Domain\RepositoryInterfaces\FixedAssetRepositoryInterface;
use Modules\Asset\Infrastructure\Persistence\Eloquent\Models\FixedAssetModel;

class EloquentFixedAssetRepository implements FixedAssetRepositoryInterface
{
    public function __construct(private readonly FixedAssetModel $model) {}

    public function findById(int $id): ?FixedAsset
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByCode(int $tenantId, string $code): ?FixedAsset
    {
        $m = $this->model->newQuery()->where('tenant_id', $tenantId)->where('code', $code)->first();
        return $m ? $this->toEntity($m) : null;
    }

    public function findAllByTenant(int $tenantId, array $filters = []): array
    {
        $q = $this->model->newQuery()->where('tenant_id', $tenantId);
        if (!empty($filters['status'])) $q->where('status', $filters['status']);
        if (!empty($filters['category'])) $q->where('category', $filters['category']);
        return $q->orderBy('name')->get()->map(fn($m) => $this->toEntity($m))->all();
    }

    public function create(array $data): FixedAsset
    {
        return $this->toEntity($this->model->newQuery()->create($data));
    }

    public function update(int $id, array $data): ?FixedAsset
    {
        $m = $this->model->newQuery()->find($id);
        if (!$m) return null;
        $m->update($data);
        return $this->toEntity($m->fresh());
    }

    public function delete(int $id): bool
    {
        return (bool) $this->model->newQuery()->find($id)?->delete();
    }

    private function toEntity(FixedAssetModel $m): FixedAsset
    {
        return new FixedAsset(
            $m->id, $m->tenant_id, $m->code, $m->name, $m->description,
            $m->category, $m->location_id, $m->assigned_to,
            (float) $m->purchase_cost, (float) $m->residual_value,
            (int) $m->useful_life_months, $m->depreciation_method,
            $m->asset_account_id, $m->depreciation_account_id,
            $m->status, $m->purchase_date, $m->disposal_date,
            $m->created_at, $m->updated_at,
        );
    }
}
