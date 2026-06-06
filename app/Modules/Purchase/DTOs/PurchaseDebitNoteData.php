<?php

declare(strict_types=1);

namespace Modules\Purchase\DTOs;

final readonly class PurchaseDebitNoteData
{
    public function __construct(
        public int $tenantId,
        public string $debitNoteDate,
        public string $amount,
        public ?int $organizationUnitId = null,
        public ?string $debitNoteNumber = null,
        public ?string $supplierType = null,
        public ?int $supplierId = null,
        public ?int $purchaseReturnId = null,
        public ?string $sourceType = null,
        public ?int $sourceId = null,
        public ?string $reason = null,
    ) {}
}
