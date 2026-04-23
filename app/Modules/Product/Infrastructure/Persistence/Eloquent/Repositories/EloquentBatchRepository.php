<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Product\Domain\Entities\Batch;
use Modules\Product\Domain\RepositoryInterfaces\BatchRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\BatchModel;

class EloquentBatchRepository extends EloquentRepository implements BatchRepositoryInterface
{
    public function __construct(BatchModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (BatchModel $model): Batch => $this->mapModelToDomainEntity($model));
    }

    public function save(Batch $entity): Batch
    {
        $data = [
            'tenant_id' => $entity->getTenantId(),
            'product_id' => $entity->getProductId(),
            'variant_id' => $entity->getVariantId(),
            'batch_number' => $entity->getBatchNumber(),
            'lot_number' => $entity->getLotNumber(),
            'manufacture_date' => $entity->getManufacturedDate()?->format('Y-m-d'),
            'expiry_date' => $entity->getExpiryDate()?->format('Y-m-d'),
            'quantity' => $entity->getQuantity(),
            'status' => $entity->getStatus(),
            'notes' => $entity->getNotes(),
            'metadata' => $entity->getMetadata(),
        ];

        if ($entity->getId()) {
            $model = $this->update($entity->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var BatchModel $model */
        return $this->toDomainEntity($model);
    }

    public function find(int|string $id, array $columns = ['*']): ?Batch
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(BatchModel $model): Batch
    {
        return new Batch(
            tenantId: (int) $model->tenant_id,
            productId: (int) $model->product_id,
            batchNumber: (string) $model->batch_number,
            variantId: $model->variant_id !== null ? (int) $model->variant_id : null,
            lotNumber: $model->lot_number,
            manufacturedDate: $model->manufacture_date ? new \DateTimeImmutable($model->manufacture_date->format('Y-m-d')) : null,
            expiryDate: $model->expiry_date ? new \DateTimeImmutable($model->expiry_date->format('Y-m-d')) : null,
            quantity: (string) $model->quantity,
            status: (string) $model->status,
            notes: $model->notes,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            id: (int) $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
