<?php

declare(strict_types=1);

namespace Modules\Purchase\DTOs;

final readonly class PurchasePaymentPreviewData
{
    /**
     * @param  list<array<string, mixed>>  $lines
     * @param  list<array<string, mixed>>  $allocations
     */
    public function __construct(
        public int $tenantId,
        public ?int $organizationUnitId,
        public string $paymentDate,
        public string $amount,
        public string $lineTotal,
        public string $allocationTotal,
        public string $unappliedAmount,
        public string $supplierType,
        public int $supplierId,
        public ?int $currencyId,
        public string $exchangeRate,
        public ?string $referenceNumber,
        public array $lines,
        public array $allocations,
    ) {}
}
