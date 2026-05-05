<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

/**
 * Input for a valuation engine / cost-layer receipt operation.
 */
readonly class CostLayerInboundData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $productId,
        public readonly ?int $variantId,
        public readonly ?int $batchId,
        public readonly int $locationId,
        public readonly string $valuationMethod,
        public readonly string $layerDate,
        public readonly string $quantity,
        public readonly string $unitCost,
        public readonly ?string $referenceType,
        public readonly ?int $referenceId,
    ) {}
}
