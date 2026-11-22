<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

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
