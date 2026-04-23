<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\DeleteProductAttachmentServiceInterface;
use Modules\Product\Domain\Exceptions\ProductAttachmentNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductAttachmentRepositoryInterface;

class DeleteProductAttachmentService extends BaseService implements DeleteProductAttachmentServiceInterface
{
    public function __construct(private readonly ProductAttachmentRepositoryInterface $productAttachmentRepository)
    {
        parent::__construct($productAttachmentRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $entity = $this->productAttachmentRepository->find($id);

        if (! $entity) {
            throw new ProductAttachmentNotFoundException($id);
        }

        return $this->productAttachmentRepository->delete($id);
    }
}
