<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Application\Commands;

/**
 * Command to create a new Bill of Materials.
 */
final class CreateBomCommand
{
    /**
     * @param array<int, array{component_product_id: int, component_variant_id: int|null, quantity: string, notes: string|null}> $lines
     */
    public function __construct(
        public readonly int     $tenantId,
        public readonly int     $productId,
        public readonly ?int    $variantId,
        public readonly string  $outputQuantity,
        public readonly ?string $reference,
        public readonly array   $lines,
        public readonly int     $createdBy,
    ) {}
}
