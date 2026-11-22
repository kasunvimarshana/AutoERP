<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payment\DTOs;

final class UpdatePaymentData
{
    /**
     * @param array<int, PaymentLineData> $lines
     * @param array<int, PaymentAllocationData> $allocations
     */
    public function __construct(
        public readonly int $expectedVersion,
        public readonly string $paymentDate,
        public readonly ?string $partyType,
        public readonly ?int $partyId,
        public readonly ?int $currencyId,
        public readonly string $exchangeRate,
        public readonly ?string $referenceNumber,
        public readonly ?string $chequeNumber,
        public readonly ?string $chequeDate,
        public readonly ?string $payeeName,
        public readonly ?string $notes,
        public readonly ?int $updatedBy,
        public readonly array $lines,
        public readonly array $allocations,
    ) {}
}
