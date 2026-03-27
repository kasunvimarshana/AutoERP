<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Illuminate\Support\Str;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\UploadProductImageServiceInterface;
use Modules\Product\Application\DTOs\ProductImageData;
use Modules\Product\Domain\Entities\ProductImage;
use Modules\Product\Domain\Exceptions\ProductNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductImageRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;

class UploadProductImageService extends BaseService implements UploadProductImageServiceInterface
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        protected ProductImageRepositoryInterface $imageRepository,
        protected FileStorageServiceInterface $storage
    ) {
        parent::__construct($productRepository);
    }

    protected function handle(array $data): ProductImage
    {
        $productId = $data['product_id'];
        $fileInfo = $data['file'];
        $sortOrder = $data['sort_order'] ?? 0;
        $isPrimary = (bool) ($data['is_primary'] ?? false);
        $metadata = $data['metadata'] ?? null;

        $product = $this->productRepository->find($productId);
        if (! $product) {
            throw new ProductNotFoundException($productId);
        }

        $uuid = (string) Str::uuid();
        $path = $this->storage->store($fileInfo['tmp_path'], "products/{$productId}", $fileInfo['name']);

        $image = new ProductImage(
            tenantId: $product->getTenantId(),
            productId: $productId,
            uuid: $uuid,
            name: $fileInfo['name'],
            filePath: $path,
            mimeType: $fileInfo['mime_type'],
            size: $fileInfo['size'],
            sortOrder: $sortOrder,
            isPrimary: $isPrimary,
            metadata: is_array($metadata) ? $metadata : null,
        );

        return $this->imageRepository->save($image);
    }
}
