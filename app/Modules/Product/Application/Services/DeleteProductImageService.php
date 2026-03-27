<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\DeleteProductImageServiceInterface;
use Modules\Product\Domain\Exceptions\ProductImageNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductImageRepositoryInterface;

class DeleteProductImageService extends BaseService implements DeleteProductImageServiceInterface
{
    public function __construct(
        protected ProductImageRepositoryInterface $imageRepository,
        protected FileStorageServiceInterface $storage
    ) {
        parent::__construct($imageRepository);
    }

    protected function handle(array $data): bool
    {
        $imageId = $data['image_id'];
        $image = $this->imageRepository->find($imageId);

        if (! $image) {
            throw new ProductImageNotFoundException($imageId);
        }

        $this->storage->delete($image->getFilePath());

        return $this->imageRepository->delete($imageId);
    }
}
