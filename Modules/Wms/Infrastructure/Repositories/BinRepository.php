<?php

declare(strict_types=1);

namespace Modules\Wms\Infrastructure\Repositories;

use App\Shared\Abstractions\BaseRepository;
use Modules\Wms\Domain\Contracts\BinRepositoryInterface;
use Modules\Wms\Domain\Entities\Bin;
use Modules\Wms\Infrastructure\Models\BinModel;

class BinRepository extends BaseRepository implements BinRepositoryInterface
{
    protected function model(): string
    {
        return BinModel::class;
    }

    public function findById(int $id, int $tenantId): ?Bin
    {
        $model = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findByAisle(int $tenantId, int $aisleId): array
    {
        return $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('aisle_id', $aisleId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->map(fn (BinModel $m) => $this->toDomain($m))
            ->all();
    }

    public function save(Bin $bin): Bin
    {
        if ($bin->id !== null) {
            $model = $this->newQuery()
                ->where('tenant_id', $bin->tenantId)
                ->findOrFail($bin->id);
        } else {
            $model = new BinModel;
            $model->tenant_id = $bin->tenantId;
            $model->aisle_id = $bin->aisleId;
            $model->code = $bin->code;
            $model->current_capacity = $bin->currentCapacity;
        }

        $model->description = $bin->description;
        $model->max_capacity = $bin->maxCapacity;
        $model->is_active = $bin->isActive;
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

    private function toDomain(BinModel $model): Bin
    {
        return new Bin(
            id: $model->id,
            tenantId: $model->tenant_id,
            aisleId: $model->aisle_id,
            code: $model->code,
            description: $model->description,
            maxCapacity: $model->max_capacity !== null ? (int) $model->max_capacity : null,
            currentCapacity: (int) $model->current_capacity,
            isActive: (bool) $model->is_active,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }
}
