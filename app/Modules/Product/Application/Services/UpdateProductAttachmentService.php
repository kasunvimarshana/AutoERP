<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\UpdateProductAttachmentServiceInterface;
use Modules\Product\Application\DTOs\ProductAttachmentData;
use Modules\Product\Domain\Entities\ProductAttachment;
use Modules\Product\Domain\Exceptions\ProductAttachmentNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductAttachmentRepositoryInterface;

class UpdateProductAttachmentService extends BaseService implements UpdateProductAttachmentServiceInterface
{
    public function __construct(private readonly ProductAttachmentRepositoryInterface $productAttachmentRepository)
    {
        parent::__construct($productAttachmentRepository);
    }

    protected function handle(array $data): ProductAttachment
    {
        $id = (int) ($data['id'] ?? 0);
        $entity = $this->productAttachmentRepository->find($id);

        if (! $entity) {
            throw new ProductAttachmentNotFoundException($id);
        }

        $dto = ProductAttachmentData::fromArray($data);
        $entity->update(
            fileName: $dto->file_name,
            filePath: $dto->file_path,
            fileType: $dto->file_type,
            fileSize: $dto->file_size,
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
