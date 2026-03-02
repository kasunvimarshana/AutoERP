<?php

declare(strict_types=1);

namespace Modules\Wms\Infrastructure\Repositories;

use App\Shared\Abstractions\BaseRepository;
use Modules\Wms\Domain\Contracts\AisleRepositoryInterface;
use Modules\Wms\Domain\Entities\Aisle;
use Modules\Wms\Infrastructure\Models\AisleModel;

class AisleRepository extends BaseRepository implements AisleRepositoryInterface
{
    protected function model(): string
    {
        return AisleModel::class;
    }

    public function findById(int $id, int $tenantId): ?Aisle
    {
        $model = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findByZone(int $tenantId, int $zoneId): array
    {
        return $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('zone_id', $zoneId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (AisleModel $m) => $this->toDomain($m))
            ->all();
    }

    public function save(Aisle $aisle): Aisle
    {
        if ($aisle->id !== null) {
            $model = $this->newQuery()
                ->where('tenant_id', $aisle->tenantId)
                ->findOrFail($aisle->id);
        } else {
            $model = new AisleModel;
            $model->tenant_id = $aisle->tenantId;
            $model->zone_id = $aisle->zoneId;
            $model->code = $aisle->code;
        }

        $model->name = $aisle->name;
        $model->description = $aisle->description;
        $model->sort_order = $aisle->sortOrder;
        $model->is_active = $aisle->isActive;
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

    private function toDomain(AisleModel $model): Aisle
    {
        return new Aisle(
            id: $model->id,
            tenantId: $model->tenant_id,
            zoneId: $model->zone_id,
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
