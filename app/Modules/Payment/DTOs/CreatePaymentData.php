<?php

declare(strict_types=1);

namespace Modules\Payment\DTOs;

use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentType;

final readonly class CreatePaymentData
{
    public function __construct(
        public int $tenantId,
        public PaymentType $paymentType,
        public PaymentDirection $direction,
        public string $paymentDate,
        public ?int $organizationUnitId = null,
        public ?string $partyType = null,
        public ?int $partyId = null,
        public ?string $sourceType = null,
        public ?int $sourceId = null,
        public ?int $originalPaymentId = null,
        public ?int $currencyId = null,
        public string $exchangeRate = '1.000000',
        public ?string $referenceNumber = null,
        public ?string $notes = null,
        public ?int $createdBy = null,
        public array $lines = [],
        public array $allocations = [],
        public ?string $chequeNumber = null,
        public ?string $chequeDate = null,
        public ?string $payeeName = null,
        public ?array $metadata = null,
        public ?string $idempotencyKey = null,
    ) {}
}
