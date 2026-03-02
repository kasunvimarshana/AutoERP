<?php

declare(strict_types=1);

namespace Modules\Product\Application\Commands;

/**
 * Command to replace all images for a product.
 *
 * Each element in $images is an associative array with keys:
 *   - image_path  (string, required) — URL or storage path
 *   - alt_text    (string|null, optional)
 *   - sort_order  (int, optional, default 0)
 *   - is_primary  (bool, optional, default false)
 */
final readonly class SetProductImagesCommand
{
    /**
     * @param  array<int, array{image_path: string, alt_text: string|null, sort_order: int, is_primary: bool}>  $images
     */
    public function __construct(
        public readonly int $productId,
        public readonly int $tenantId,
        public readonly array $images,
    ) {}
}
