<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Product\Application\Contracts\BulkUploadProductImagesServiceInterface;
use Modules\Product\Application\Contracts\ImageStorageStrategyInterface;
use Modules\Product\Domain\Entities\ProductImage;
use Modules\Product\Domain\Exceptions\ProductNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductImageRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;

class BulkUploadProductImagesService implements BulkUploadProductImagesServiceInterface
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductImageRepositoryInterface $imageRepository,
        private readonly ImageStorageStrategyInterface $storageStrategy
    ) {}

    /**
     * Upload multiple images for a product inside a single transaction.
     *
     * @return Collection<int, ProductImage>
     */
    public function execute(array $data): Collection
    {
        return DB::transaction(function () use ($data): Collection {
            $productId       = (int) $data['product_id'];
            $files           = $data['files'] ?? [];
            $sortOrderStart  = (int) ($data['sort_order_start'] ?? 0);
            $primaryIndex    = isset($data['is_primary_index']) ? (int) $data['is_primary_index'] : null;
            $metadata        = isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : null;

            $product = $this->productRepository->find($productId);
            if (! $product) {
                throw new ProductNotFoundException($productId);
            }

            $saved = new Collection;

            foreach ($files as $index => $file) {
                /** @var UploadedFile $file */
                $isPrimary = ($primaryIndex !== null && $primaryIndex === $index);
                $uuid      = (string) Str::uuid();
                $path      = $this->storageStrategy->store($file, $productId);

                // Note: the same $metadata array is applied to every image.
                // For per-image metadata, extend the API to accept a keyed list.
                $image = new ProductImage(
                    tenantId:  $product->getTenantId(),
                    productId: $productId,
                    uuid:      $uuid,
                    name:      $file->getClientOriginalName(),
                    filePath:  $path,
                    mimeType:  (string) $file->getMimeType(),
                    size:      (int) $file->getSize(),
                    sortOrder: $sortOrderStart + $index,
                    isPrimary: $isPrimary,
                    metadata:  $metadata,
                );

                $saved->push($this->imageRepository->save($image));
            }

            return $saved;
        });
    }
}
