<?php

declare(strict_types=1);

namespace Modules\Product\Application\Contracts;

use Illuminate\Support\Collection;

/**
 * Contract for uploading multiple product images in a single operation.
 */
interface BulkUploadProductImagesServiceInterface
{
    /**
     * Upload multiple images for a product.
     *
     * Expected $data keys:
     *   - product_id (int)
     *   - files       (array of \Illuminate\Http\UploadedFile)
     *   - sort_order_start (int, optional, default 0) – first image gets this order, incremented for each subsequent
     *   - is_primary_index (int|null, optional) – index of the file that should be marked primary
     *   - metadata    (array|null, optional) – applied to every uploaded image
     *
     * @return Collection<int, \Modules\Product\Domain\Entities\ProductImage>
     */
    public function execute(array $data): Collection;
}
