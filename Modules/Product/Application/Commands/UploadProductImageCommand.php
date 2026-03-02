<?php

declare(strict_types=1);

namespace Modules\Product\Application\Commands;

/**
 * Command to add a single uploaded image to a product.
 *
 * The file has already been stored to the configured filesystem disk by the
 * HTTP layer before this command is dispatched. The handler records the
 * metadata and appends the new image to the product's image collection.
 */
final readonly class UploadProductImageCommand
{
    public function __construct(
        public readonly int $productId,
        public readonly int $tenantId,
        /** Storage-relative path returned by Laravel's Storage::put / file->store(). */
        public readonly string $storagePath,
        public readonly ?string $altText = null,
        public readonly int $sortOrder = 0,
        public readonly bool $isPrimary = false,
    ) {}
}
