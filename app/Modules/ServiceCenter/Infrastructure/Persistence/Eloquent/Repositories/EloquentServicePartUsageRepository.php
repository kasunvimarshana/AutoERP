<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\ServiceCenter\Domain\Entities\ServicePartUsage;
use Modules\ServiceCenter\Domain\RepositoryInterfaces\ServicePartUsageRepositoryInterface;
use Modules\ServiceCenter\Infrastructure\Persistence\Eloquent\Models\ServicePartUsageModel;

class EloquentServicePartUsageRepository implements ServicePartUsageRepositoryInterface
{
    public function create(ServicePartUsage $part): void
    {
        ServicePartUsageModel::create([
            'id' => $part->getId(),
            'service_order_id' => $part->getServiceOrderId(),
            'inventory_item_id' => $part->getInventoryItemId(),
            'part_name' => $part->getPartName(),
            'part_number' => $part->getPartNumber(),
            'quantity' => $part->getQuantity(),
            'unit_cost' => $part->getUnitCost(),
            'total_cost' => $part->getTotalCost(),
        ]);
    }

    public function findById(string $id): ?ServicePartUsage
    {
        $model = ServicePartUsageModel::find($id);
        return $model ? $this->toDomain($model) : null;
    }

    public function getByServiceOrder(string $serviceOrderId): array
    {
        $models = ServicePartUsageModel::where('service_order_id', $serviceOrderId)->get();
        return $models->map(fn ($m) => $this->toDomain($m))->all();
    }

    public function sumCostByServiceOrder(string $serviceOrderId): string
    {
        $sum = ServicePartUsageModel::where('service_order_id', $serviceOrderId)->sum('total_cost');
        return number_format((float) $sum, 6, '.', '');
    }

    public function delete(string $id): void
    {
        ServicePartUsageModel::findOrFail($id)->delete();
    }

    private function toDomain(ServicePartUsageModel $model): ServicePartUsage
    {
        return new ServicePartUsage(
            id: (string) $model->id,
            serviceOrderId: (string) $model->service_order_id,
            inventoryItemId: $model->inventory_item_id !== null ? (string) $model->inventory_item_id : null,
            partName: (string) $model->part_name,
            partNumber: (string) $model->part_number,
            quantity: (int) $model->quantity,
            unitCost: (string) $model->unit_cost,
            totalCost: (string) $model->total_cost,
        );
    }
}
