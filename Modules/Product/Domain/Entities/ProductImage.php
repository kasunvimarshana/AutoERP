<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

/**
 * Represents a single image associated with a product.
 *
 * A product may have zero or more images. One image may be flagged as primary
 * (used as the default display image). Images are ordered by sort_order (ASC).
 *
 * Images can originate from two sources:
 *  - 'url'    — an external URL (e.g. a CDN link) stored directly as-is.
 *  - 'upload' — a file uploaded to and stored on the platform's configured
 *               filesystem disk; `imagePath` holds the storage-relative path.
 */
final class ProductImage
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $productId,
        public readonly int $tenantId,
        /** Full URL or storage-relative path to the image file. */
        public readonly string $imagePath,
        /** Optional alternative text for accessibility / SEO. */
        public readonly ?string $altText,
        /** Zero-based ordering index; lower values appear first. */
        public readonly int $sortOrder,
        /** Whether this is the product's primary (featured) image. */
        public readonly bool $isPrimary,
        /** Source type: 'url' for external links, 'upload' for stored files. */
        public readonly string $imageSourceType = 'url',
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
    ) {}
}
