<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Product\Application\Commands\DeleteProductImageCommand;
use Modules\Product\Application\Commands\SetProductImagesCommand;
use Modules\Product\Application\Commands\UploadProductImageCommand;
use Modules\Product\Application\Handlers\DeleteProductImageHandler;
use Modules\Product\Application\Handlers\SetProductImagesHandler;
use Modules\Product\Application\Handlers\UploadProductImageHandler;
use Modules\Product\Domain\Contracts\ProductImageRepositoryInterface;
use Modules\Product\Domain\Contracts\ProductRepositoryInterface;
use Modules\Product\Domain\Entities\ProductImage;

/**
 * Service orchestrating all product image operations.
 *
 * Controllers must interact with product image data exclusively through this
 * service. Read operations are fulfilled directly via the repository contract;
 * write operations are delegated to the appropriate command handlers.
 */
class ProductImageService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductImageRepositoryInterface $productImageRepository,
        private readonly SetProductImagesHandler $setImagesHandler,
        private readonly UploadProductImageHandler $uploadImageHandler,
        private readonly DeleteProductImageHandler $deleteImageHandler,
    ) {}

    /**
     * Verify that a product exists within the given tenant.
     */
    public function productExists(int $productId, int $tenantId): bool
    {
        return $this->productRepository->findById($productId, $tenantId) !== null;
    }

    /**
     * Retrieve all images for a product, ordered by sort_order.
     *
     * @return ProductImage[]
     */
    public function listImages(int $productId, int $tenantId): array
    {
        return $this->productImageRepository->findByProduct($productId, $tenantId);
    }

    /**
     * Replace all URL-sourced images for a product and return the new set.
     *
     * @return ProductImage[]
     */
    public function setImages(SetProductImagesCommand $command): array
    {
        return $this->setImagesHandler->handle($command);
    }

    /**
     * Persist an uploaded image and return the stored entity.
     */
    public function uploadImage(UploadProductImageCommand $command): ProductImage
    {
        return $this->uploadImageHandler->handle($command);
    }

    /**
     * Remove a single product image by its identifier.
     */
    public function deleteImage(DeleteProductImageCommand $command): void
    {
        $this->deleteImageHandler->handle($command);
    }
}
