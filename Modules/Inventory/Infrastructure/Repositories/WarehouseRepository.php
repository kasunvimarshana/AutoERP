<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Repositories;

use App\Shared\Abstractions\BaseRepository;
use Modules\Inventory\Domain\Contracts\WarehouseRepositoryInterface;
use Modules\Inventory\Domain\Entities\Warehouse;
use Modules\Inventory\Infrastructure\Models\WarehouseModel;

class WarehouseRepository extends BaseRepository implements WarehouseRepositoryInterface
{
    protected function model(): string
    {
        return WarehouseModel::class;
    }

    public function findById(int $id, int $tenantId): ?Warehouse
    {
        $model = $this->newQuery()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByCode(string $code, int $tenantId): ?Warehouse
    {
        $model = $this->newQuery()
            ->where('code', strtoupper($code))
            ->where('tenant_id', $tenantId)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findAll(int $tenantId, int $page = 1, int $perPage = 25): array
    {
        $paginator = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->getCollection()->map(fn (WarehouseModel $m) => $this->toDomain($m))->all(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
        ];
    }

    public function save(Warehouse $warehouse): Warehouse
    {
        if ($warehouse->id !== null) {
            $model = $this->newQuery()
                ->where('id', $warehouse->id)
                ->where('tenant_id', $warehouse->tenantId)
                ->firstOrFail();
        } else {
            $model = new WarehouseModel;
        }

        $model->tenant_id = $warehouse->tenantId;
        $model->code = $warehouse->code;
        $model->name = $warehouse->name;
        $model->address = $warehouse->address;
        $model->status = $warehouse->status;
        $model->save();

        return $this->toDomain($model);
    }

    private function toDomain(WarehouseModel $model): Warehouse
    {
        return new Warehouse(
            id: $model->id,
            tenantId: $model->tenant_id,
            code: $model->code,
            name: $model->name,
            address: $model->address,
            status: $model->status,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }
}
