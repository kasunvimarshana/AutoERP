<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Service\Domain\Entities\ServicePart;
use Modules\Service\Domain\RepositoryInterfaces\ServicePartRepositoryInterface;
use Modules\Service\Infrastructure\Persistence\Eloquent\Models\ServicePartModel;

class EloquentServicePartRepository implements ServicePartRepositoryInterface
{
    public function __construct(private readonly ServicePartModel $model) {}

    public function findById(int $tenantId, int $id): ?ServicePart
    {
        /** @var ServicePartModel|null $model */
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();

        return $model !== null ? $this->mapModelToEntity($model) : null;
    }

    public function findByWorkOrder(int $tenantId, int $workOrderId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('service_work_order_id', $workOrderId)
            ->orderBy('id')
            ->get()
            ->map(fn (ServicePartModel $m) => $this->mapModelToEntity($m))
            ->all();
    }

    public function save(ServicePart $part): ServicePart
    {
        $payload = [
            'tenant_id' => $part->getTenantId(),
            'org_unit_id' => $part->getOrgUnitId(),
            'row_version' => $part->getRowVersion(),
            'service_work_order_id' => $part->getServiceWorkOrderId(),
            'service_task_id' => $part->getServiceTaskId(),
            'product_id' => $part->getProductId(),
            'part_source' => $part->getPartSource(),
            'description' => $part->getDescription(),
            'quantity' => $part->getQuantity(),
            'uom_id' => $part->getUomId(),
            'unit_cost' => $part->getUnitCost(),
            'unit_price' => $part->getUnitPrice(),
            'line_amount' => $part->getLineAmount(),
            'is_returned' => $part->isReturned(),
            'is_warranty_covered' => $part->isWarrantyCovered(),
            'stock_reference_type' => $part->getStockReferenceType(),
            'stock_reference_id' => $part->getStockReferenceId(),
            'metadata' => $part->getMetadata(),
        ];

        $id = $part->getId();

        if ($id !== null) {
            $this->model->newQuery()
                ->where('tenant_id', $part->getTenantId())
                ->where('id', $id)
                ->update($payload);

            /** @var ServicePartModel $saved */
            $saved = $this->model->newQuery()
                ->where('tenant_id', $part->getTenantId())
                ->where('id', $id)
                ->firstOrFail();
        } else {
            /** @var ServicePartModel $saved */
            $saved = $this->model->newQuery()->create($payload);
        }

        return $this->mapModelToEntity($saved);
    }

    public function delete(int $tenantId, int $id): bool
    {
        return (bool) $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->delete();
    }

    private function mapModelToEntity(ServicePartModel $model): ServicePart
    {
        return new ServicePart(
            tenantId: (int) $model->tenant_id,
            serviceWorkOrderId: (int) $model->service_work_order_id,
            orgUnitId: $model->org_unit_id !== null ? (int) $model->org_unit_id : null,
            serviceTaskId: $model->service_task_id !== null ? (int) $model->service_task_id : null,
            productId: $model->product_id !== null ? (int) $model->product_id : null,
            partSource: (string) $model->part_source,
            description: $model->description,
            quantity: (float) $model->quantity,
            uomId: $model->uom_id !== null ? (int) $model->uom_id : null,
            unitCost: (float) $model->unit_cost,
            unitPrice: (float) $model->unit_price,
            lineAmount: (float) $model->line_amount,
            isReturned: (bool) $model->is_returned,
            isWarrantyCovered: (bool) $model->is_warranty_covered,
            stockReferenceType: $model->stock_reference_type,
            stockReferenceId: $model->stock_reference_id !== null ? (int) $model->stock_reference_id : null,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            rowVersion: (int) $model->row_version,
            createdAt: $model->created_at !== null ? new \DateTimeImmutable((string) $model->created_at) : null,
            updatedAt: $model->updated_at !== null ? new \DateTimeImmutable((string) $model->updated_at) : null,
            id: (int) $model->id,
        );
    }
}
