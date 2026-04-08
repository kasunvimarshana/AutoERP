<?php

declare(strict_types=1);

namespace Modules\Category\Application\Services;

use Illuminate\Support\Str;
use Modules\Category\Application\Contracts\UploadCategoryImageServiceInterface;
use Modules\Category\Domain\Entities\CategoryImage;
use Modules\Category\Domain\Exceptions\CategoryNotFoundException;
use Modules\Category\Domain\RepositoryInterfaces\CategoryImageRepositoryInterface;
use Modules\Category\Domain\RepositoryInterfaces\CategoryRepositoryInterface;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Core\Application\Services\BaseService;

class UploadCategoryImageService extends BaseService implements UploadCategoryImageServiceInterface
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        protected CategoryImageRepositoryInterface $imageRepository,
        protected FileStorageServiceInterface $storage
    ) {
        parent::__construct($categoryRepository);
    }

    protected function handle(array $data): CategoryImage
    {
        $categoryId = $data['category_id'];
        $fileInfo = $data['file'];
        $metadata = $data['metadata'] ?? null;

        $category = $this->categoryRepository->find($categoryId);
        if (! $category) {
            throw new CategoryNotFoundException($categoryId);
        }

        // Remove existing image if one exists
        $this->imageRepository->deleteByCategory($categoryId);

        $uuid = (string) Str::uuid();
        $path = $this->storage->store($fileInfo['tmp_path'], "categories/{$categoryId}", $fileInfo['name']);

        $image = new CategoryImage(
            tenantId: $category->getTenantId(),
            categoryId: $categoryId,
            uuid: $uuid,
            name: $fileInfo['name'],
            filePath: $path,
            mimeType: $fileInfo['mime_type'],
            size: $fileInfo['size'],
            metadata: is_array($metadata) ? $metadata : null,
        );

        return $this->imageRepository->save($image);
    }
}
