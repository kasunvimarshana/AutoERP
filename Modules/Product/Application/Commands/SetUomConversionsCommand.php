<?php

declare(strict_types=1);

namespace Modules\Product\Application\Commands;

/**
 * Command to replace all UOM conversions for a product.
 *
 * Each element in $conversions is an associative array with keys:
 *   - from_uom (string, required) — source unit of measure
 *   - to_uom   (string, required) — target unit of measure
 *   - factor   (string|float, required) — conversion multiplier (>0)
 */
final readonly class SetUomConversionsCommand
{
    /**
     * @param  array<int, array{from_uom: string, to_uom: string, factor: string|float}>  $conversions
     */
    public function __construct(
        public readonly int $productId,
        public readonly int $tenantId,
        public readonly array $conversions,
    ) {}
}
