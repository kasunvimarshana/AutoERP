<?php

declare(strict_types=1);

namespace Modules\VehicleService\DTOs;

final readonly class VehicleServicePaymentData
{
    /** @param array<string, mixed>|null $metadata */
    public function __construct(
        public int $invoiceId,
        public string $paymentDate,
        public string $amount,
        public int $paymentMethodId,
        public ?int $currencyId = null,
        public string $exchangeRate = '1.000000',
        public ?string $referenceNumber = null,
        public ?int $internalBankAccountId = null,
        public ?string $externalBankName = null,
        public ?string $externalBankBranch = null,
        public ?string $instrumentNumber = null,
        public ?string $instrumentDate = null,
        public ?string $depositDate = null,
        public ?string $realizedDate = null,
        public ?array $metadata = null,
        public ?int $createdBy = null,
    ) {}
}
