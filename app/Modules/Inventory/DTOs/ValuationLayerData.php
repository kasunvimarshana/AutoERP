<?php

declare(strict_types=1);

namespace Modules\Inventory\DTOs;

use Modules\Inventory\Enums\ValuationMethod;

final readonly class ValuationLayerData
{
    public function __construct(
        public int $tenantId,
        public int $itemId,
        public int $warehouseId,
        public ValuationMethod $valuationMethod,
        public string $originalQuantity,
        public string $unitCost,
        public ?int $organizationUnitId = null,
        public ?int $itemVariantId = null,
        public ?int $warehouseLocationId = null,
        public ?int $batchId = null,
        public ?int $movementId = null,
        public ?string $sourceType = null,
        public ?int $sourceId = null,
        public ?string $sourceLineType = null,
        public ?int $sourceLineId = null,
        public ?int $baseUomId = null,
    ) {}
}
