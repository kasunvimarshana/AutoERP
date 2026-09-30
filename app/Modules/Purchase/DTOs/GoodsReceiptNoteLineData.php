<?php

declare(strict_types=1);

namespace Modules\Purchase\DTOs;

final readonly class GoodsReceiptNoteLineData
{
    /** @param list<GoodsReceiptBatchAllocationData> $batchAllocations */
    public function __construct(
        public int $itemId,
        public string $receivedQuantity,
        public string $acceptedQuantity,
        public string $unitPrice,
        public ?int $purchaseOrderLineId = null,
        public ?int $itemVariantId = null,
        public ?string $description = null,
        public ?int $uomId = null,
        public ?int $orderedUomId = null,
        public ?int $baseUomId = null,
        public string $uomConversionFactor = '1.000000',
        public ?string $baseReceivedQuantity = null,
        public ?string $baseAcceptedQuantity = null,
        public string $orderedQuantity = '0.000000',
        public string $rejectedQuantity = '0.000000',
        public string $discountAmount = '0.000000',
        public string $taxAmount = '0.000000',
        public string $chargeAmount = '0.000000',
        public ?int $taxGroupId = null,
        public array $batchAllocations = [],
    ) {}
}
