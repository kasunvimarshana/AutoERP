<?php

declare(strict_types=1);

namespace Modules\Category\Application\Services;

use Modules\Category\Application\Contracts\DeleteCategoryImageServiceInterface;
use Modules\Category\Domain\Exceptions\CategoryImageNotFoundException;
use Modules\Category\Domain\RepositoryInterfaces\CategoryImageRepositoryInterface;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Core\Application\Services\BaseService;

class DeleteCategoryImageService extends BaseService implements DeleteCategoryImageServiceInterface
{
    public function __construct(
        protected CategoryImageRepositoryInterface $imageRepository,
        protected FileStorageServiceInterface $storage
    ) {
        parent::__construct($imageRepository);
    }

    protected function handle(array $data): bool
    {
        $imageId = $data['image_id'];
        $image = $this->imageRepository->find($imageId);

        if (! $image) {
            throw new CategoryImageNotFoundException($imageId);
        }

        $this->storage->delete($image->getFilePath());

        return $this->imageRepository->delete($imageId);
    }
}
