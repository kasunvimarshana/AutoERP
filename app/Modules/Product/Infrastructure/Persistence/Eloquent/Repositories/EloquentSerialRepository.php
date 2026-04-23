<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Product\Domain\Entities\Serial;
use Modules\Product\Domain\RepositoryInterfaces\SerialRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\SerialModel;

class EloquentSerialRepository extends EloquentRepository implements SerialRepositoryInterface
{
    public function __construct(SerialModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (SerialModel $model): Serial => $this->mapModelToDomainEntity($model));
    }

    public function save(Serial $entity): Serial
    {
        $data = [
            'tenant_id' => $entity->getTenantId(),
            'product_id' => $entity->getProductId(),
            'variant_id' => $entity->getVariantId(),
            'batch_id' => $entity->getBatchId(),
            'serial_number' => $entity->getSerialNumber(),
            'status' => $entity->getStatus(),
            'sold_at' => $entity->getSoldAt()?->format('Y-m-d H:i:s'),
            'notes' => $entity->getNotes(),
            'metadata' => $entity->getMetadata(),
        ];

        if ($entity->getId()) {
            $model = $this->update($entity->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var SerialModel $model */
        return $this->toDomainEntity($model);
    }

    public function find(int|string $id, array $columns = ['*']): ?Serial
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(SerialModel $model): Serial
    {
        return new Serial(
            tenantId: (int) $model->tenant_id,
            productId: (int) $model->product_id,
            serialNumber: (string) $model->serial_number,
            variantId: $model->variant_id !== null ? (int) $model->variant_id : null,
            batchId: $model->batch_id !== null ? (int) $model->batch_id : null,
            status: (string) $model->status,
            soldAt: $model->sold_at ? new \DateTimeImmutable($model->sold_at->format('Y-m-d H:i:s')) : null,
            notes: $model->notes,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            id: (int) $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
