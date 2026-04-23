<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Product\Domain\Entities\ProductAttachment;
use Modules\Product\Domain\RepositoryInterfaces\ProductAttachmentRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductAttachmentModel;

class EloquentProductAttachmentRepository extends EloquentRepository implements ProductAttachmentRepositoryInterface
{
    public function __construct(ProductAttachmentModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (ProductAttachmentModel $model): ProductAttachment => $this->mapModelToDomainEntity($model));
    }

    public function save(ProductAttachment $entity): ProductAttachment
    {
        $data = [
            'tenant_id' => $entity->getTenantId(),
            'product_id' => $entity->getProductId(),
            'variant_id' => $entity->getVariantId(),
            'file_name' => $entity->getFileName(),
            'file_path' => $entity->getFilePath(),
            'file_type' => $entity->getFileType(),
            'file_size' => $entity->getFileSize(),
            'type' => $entity->getType(),
            'is_primary' => $entity->isPrimary(),
            'sort_order' => $entity->getSortOrder(),
            'title' => $entity->getTitle(),
            'description' => $entity->getDescription(),
            'metadata' => $entity->getMetadata(),
        ];

        if ($entity->getId()) {
            $model = $this->update($entity->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var ProductAttachmentModel $model */
        return $this->toDomainEntity($model);
    }

    public function find(int|string $id, array $columns = ['*']): ?ProductAttachment
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(ProductAttachmentModel $model): ProductAttachment
    {
        return new ProductAttachment(
            tenantId: (int) $model->tenant_id,
            productId: (int) $model->product_id,
            fileName: (string) $model->file_name,
            filePath: (string) $model->file_path,
            fileType: (string) $model->file_type,
            fileSize: (int) $model->file_size,
            variantId: $model->variant_id !== null ? (int) $model->variant_id : null,
            type: (string) $model->type,
            isPrimary: (bool) $model->is_primary,
            sortOrder: (int) $model->sort_order,
            title: $model->title,
            description: $model->description,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            id: (int) $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
