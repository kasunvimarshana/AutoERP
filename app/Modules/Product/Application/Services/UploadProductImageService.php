<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\ImageStorageStrategyInterface;
use Modules\Product\Application\Contracts\UploadProductImageServiceInterface;
use Modules\Product\Domain\Entities\ProductImage;
use Modules\Product\Domain\Exceptions\ProductNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductImageRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;

class UploadProductImageService extends BaseService implements UploadProductImageServiceInterface
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        protected ProductImageRepositoryInterface $imageRepository,
        protected ImageStorageStrategyInterface $storageStrategy
    ) {
        parent::__construct($productRepository);
    }

    /**
     * Expected $data keys:
     *   - product_id (int)
     *   - file       (UploadedFile)
     *   - sort_order (int|null)
     *   - is_primary (bool|null)
     *   - metadata   (array|null)
     */
    protected function handle(array $data): ProductImage
    {
        $productId = (int) $data['product_id'];
        /** @var UploadedFile $file */
        $file      = $data['file'];
        $sortOrder = (int) ($data['sort_order'] ?? 0);
        $isPrimary = (bool) ($data['is_primary'] ?? false);
        $metadata  = isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : null;

        $product = $this->productRepository->find($productId);
        if (! $product) {
            throw new ProductNotFoundException($productId);
        }

        $uuid = (string) Str::uuid();
        $path = $this->storageStrategy->store($file, $productId);

        $image = new ProductImage(
            tenantId:  $product->getTenantId(),
            productId: $productId,
            uuid:      $uuid,
            name:      $file->getClientOriginalName(),
            filePath:  $path,
            mimeType:  (string) $file->getMimeType(),
            size:      (int) $file->getSize(),
            sortOrder: $sortOrder,
            isPrimary: $isPrimary,
            metadata:  $metadata,
        );

        return $this->imageRepository->save($image);
    }
}
