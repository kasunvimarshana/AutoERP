<?php

declare(strict_types=1);

namespace Modules\Payment\DTOs;

final readonly class PaymentLineData
{
    public function __construct(
        public string $amount,
        public ?int $paymentMethodId = null,
        public ?string $referenceNumber = null,
        public string $clearedAmount = '0.000000',
        public string $status = 'pending',
        public ?string $notes = null,
        public ?array $metadata = null,
        public ?int $internalBankAccountId = null,
        public ?string $instrumentDirection = null,
        public ?string $externalBankName = null,
        public ?string $externalBankBranch = null,
        public ?string $instrumentNumber = null,
        public ?string $instrumentDate = null,
        public ?string $depositDate = null,
        public ?string $realizedDate = null,
        public ?string $clearingDate = null,
        public ?string $bouncedDate = null,
        public ?string $returnedDate = null,
        public ?string $cancellationReason = null,
    ) {}
}
