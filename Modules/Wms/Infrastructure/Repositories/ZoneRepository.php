<?php

declare(strict_types=1);

namespace Modules\Wms\Infrastructure\Repositories;

use App\Shared\Abstractions\BaseRepository;
use Modules\Wms\Domain\Contracts\ZoneRepositoryInterface;
use Modules\Wms\Domain\Entities\Zone;
use Modules\Wms\Infrastructure\Models\ZoneModel;

class ZoneRepository extends BaseRepository implements ZoneRepositoryInterface
{
    protected function model(): string
    {
        return ZoneModel::class;
    }

    public function findById(int $id, int $tenantId): ?Zone
    {
        $model = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findAll(int $tenantId, int $warehouseId, int $page, int $perPage): array
    {
        $paginator = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('warehouse_id', $warehouseId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->getCollection()
                ->map(fn (ZoneModel $m) => $this->toDomain($m))
                ->all(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
        ];
    }

    public function findByWarehouse(int $tenantId, int $warehouseId): array
    {
        return $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('warehouse_id', $warehouseId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (ZoneModel $m) => $this->toDomain($m))
            ->all();
    }

    public function save(Zone $zone): Zone
    {
        if ($zone->id !== null) {
            $model = $this->newQuery()
                ->where('tenant_id', $zone->tenantId)
                ->findOrFail($zone->id);
        } else {
            $model = new ZoneModel;
            $model->tenant_id = $zone->tenantId;
            $model->warehouse_id = $zone->warehouseId;
            $model->code = $zone->code;
        }

        $model->name = $zone->name;
        $model->description = $zone->description;
        $model->sort_order = $zone->sortOrder;
        $model->is_active = $zone->isActive;
        $model->save();

        return $this->toDomain($model);
    }

    public function delete(int $id, int $tenantId): void
    {
        $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->findOrFail($id)
            ->delete();
    }

    private function toDomain(ZoneModel $model): Zone
    {
        return new Zone(
            id: $model->id,
            tenantId: $model->tenant_id,
            warehouseId: $model->warehouse_id,
            name: $model->name,
            code: $model->code,
            description: $model->description,
            sortOrder: (int) $model->sort_order,
            isActive: (bool) $model->is_active,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }
}
