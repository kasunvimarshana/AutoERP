<?php

declare(strict_types=1);

namespace Modules\Product\Application\Commands;

/**
 * Command to replace all dynamic attributes for a product.
 *
 * Each element in $attributes is an associative array with keys:
 *   - attribute_key   (string, required) — machine-readable key
 *   - attribute_label (string, required) — human-readable label
 *   - attribute_value (string, required) — the stored value
 *   - attribute_type  (string, optional, default "text")
 *   - sort_order      (int, optional, default 0)
 */
final readonly class SetProductAttributesCommand
{
    /**
     * @param  array<int, array{attribute_key: string, attribute_label: string, attribute_value: string, attribute_type: string, sort_order: int}>  $attributes
     */
    public function __construct(
        public readonly int $productId,
        public readonly int $tenantId,
        public readonly array $attributes,
    ) {}
}
