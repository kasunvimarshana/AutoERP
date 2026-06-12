<?php

declare(strict_types=1);

namespace Modules\Payment\DTOs;

use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Enums\PaymentType;

final readonly class CreatePaymentData
{
    /**
     * @param  list<PaymentLineData>  $lines
     * @param  list<PaymentAllocationData>  $allocations
     */
    public function __construct(
        public int $tenantId,
        public PaymentType $paymentType,
        public PaymentDirection $direction,
        public string $paymentDate,
        public ?int $organizationUnitId = null,
        public ?string $paymentNumber = null,
        public ?string $partyType = null,
        public ?int $partyId = null,
        public ?string $sourceType = null,
        public ?int $sourceId = null,
        public string $allocationStatus = 'unapplied',
        public ?int $currencyId = null,
        public string $exchangeRate = '1.000000',
        public ?string $referenceNumber = null,
        public PaymentStatus $status = PaymentStatus::Draft,
        public ?string $notes = null,
        public ?int $createdBy = null,
        public array $lines = [],
        public array $allocations = [],
        public ?string $chequeNumber = null,
        public ?string $chequeDate = null,
        public ?int $bankAccountId = null,
        public ?string $payeeName = null,
        public ?string $amountInWords = null,
        public ?array $metadata = null,
    ) {}
}
