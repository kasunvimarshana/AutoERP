<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Service\Domain\Entities\ServiceReturnLine;
use Modules\Service\Domain\RepositoryInterfaces\ServiceReturnLineRepositoryInterface;
use Modules\Service\Infrastructure\Persistence\Eloquent\Models\ServiceReturnLineModel;

class EloquentServiceReturnLineRepository implements ServiceReturnLineRepositoryInterface
{
    public function __construct(private readonly ServiceReturnLineModel $model) {}

    public function findById(int $tenantId, int $id): ?ServiceReturnLine
    {
        /** @var ServiceReturnLineModel|null $model */
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();

        return $model !== null ? $this->mapModelToEntity($model) : null;
    }

    public function findByReturn(int $tenantId, int $returnId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('service_return_id', $returnId)
            ->orderBy('id')
            ->get()
            ->map(fn (ServiceReturnLineModel $m) => $this->mapModelToEntity($m))
            ->all();
    }

    public function save(ServiceReturnLine $line): ServiceReturnLine
    {
        $payload = [
            'tenant_id' => $line->getTenantId(),
            'service_return_id' => $line->getServiceReturnId(),
            'service_part_id' => $line->getServicePartId(),
            'product_id' => $line->getProductId(),
            'description' => $line->getDescription(),
            'quantity' => $line->getQuantity(),
            'uom_id' => $line->getUomId(),
            'unit_amount' => $line->getUnitAmount(),
            'line_amount' => $line->getLineAmount(),
            'stock_reference_type' => $line->getStockReferenceType(),
            'stock_reference_id' => $line->getStockReferenceId(),
            'metadata' => $line->getMetadata(),
        ];

        $id = $line->getId();

        if ($id !== null) {
            $this->model->newQuery()
                ->where('tenant_id', $line->getTenantId())
                ->where('id', $id)
                ->update($payload);

            /** @var ServiceReturnLineModel $saved */
            $saved = $this->model->newQuery()
                ->where('tenant_id', $line->getTenantId())
                ->where('id', $id)
                ->firstOrFail();
        } else {
            /** @var ServiceReturnLineModel $saved */
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

    private function mapModelToEntity(ServiceReturnLineModel $model): ServiceReturnLine
    {
        return new ServiceReturnLine(
            tenantId: (int) $model->tenant_id,
            serviceReturnId: (int) $model->service_return_id,
            servicePartId: $model->service_part_id !== null ? (int) $model->service_part_id : null,
            productId: $model->product_id !== null ? (int) $model->product_id : null,
            description: $model->description,
            quantity: (float) $model->quantity,
            uomId: $model->uom_id !== null ? (int) $model->uom_id : null,
            unitAmount: (float) $model->unit_amount,
            lineAmount: (float) $model->line_amount,
            stockReferenceType: $model->stock_reference_type,
            stockReferenceId: $model->stock_reference_id !== null ? (int) $model->stock_reference_id : null,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            createdAt: $model->created_at !== null ? new \DateTimeImmutable((string) $model->created_at) : null,
            updatedAt: $model->updated_at !== null ? new \DateTimeImmutable((string) $model->updated_at) : null,
            id: (int) $model->id,
        );
    }
}
