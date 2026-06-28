<?php

declare(strict_types=1);

namespace Modules\Tax\Data;

final readonly class TaxPaymentWithholdingData
{
    /**
     * @param list<TaxPaymentWithholdingAllocationData> $allocations
     */
    public function __construct(
        public int $tenantId,
        public ?int $organizationUnitId,
        public int $paymentId,
        public string $paymentNumber,
        public string $paymentDate,
        public array $allocations,
    ) {}
}
