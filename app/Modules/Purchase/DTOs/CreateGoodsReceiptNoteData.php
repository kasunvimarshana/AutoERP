<?php

declare(strict_types=1);

namespace Modules\Purchase\DTOs;

/**
 * @param  list<GoodsReceiptNoteLineData>  $lines
 */
final readonly class CreateGoodsReceiptNoteData
{
    public function __construct(
        public int $tenantId,
        public string $receivedDate,
        public int $warehouseId,
        public ?int $organizationUnitId = null,
        public ?int $purchaseOrderId = null,
        public ?string $grnNumber = null,
        public ?int $warehouseLocationId = null,
        public ?string $supplierType = null,
        public ?int $supplierId = null,
        public ?string $notes = null,
        public ?int $receivedBy = null,
        public array $lines = [],
    ) {}
}
