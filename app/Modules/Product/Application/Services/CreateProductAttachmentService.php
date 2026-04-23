<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\CreateProductAttachmentServiceInterface;
use Modules\Product\Application\DTOs\ProductAttachmentData;
use Modules\Product\Domain\Entities\ProductAttachment;
use Modules\Product\Domain\RepositoryInterfaces\ProductAttachmentRepositoryInterface;

class CreateProductAttachmentService extends BaseService implements CreateProductAttachmentServiceInterface
{
    public function __construct(private readonly ProductAttachmentRepositoryInterface $productAttachmentRepository)
    {
        parent::__construct($productAttachmentRepository);
    }

    protected function handle(array $data): ProductAttachment
    {
        $dto = ProductAttachmentData::fromArray($data);
        $entity = new ProductAttachment(
            tenantId: $dto->tenant_id,
            productId: $dto->product_id,
            fileName: $dto->file_name,
            filePath: $dto->file_path,
            fileType: $dto->file_type,
            fileSize: $dto->file_size,
            variantId: $dto->variant_id,
            type: $dto->type,
            isPrimary: $dto->is_primary,
            sortOrder: $dto->sort_order,
            title: $dto->title,
            description: $dto->description,
            metadata: $dto->metadata,
        );

        return $this->productAttachmentRepository->save($entity);
    }
}
