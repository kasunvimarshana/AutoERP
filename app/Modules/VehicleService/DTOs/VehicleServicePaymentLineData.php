<?php

declare(strict_types=1);

namespace Modules\VehicleService\DTOs;

final readonly class VehicleServicePaymentLineData
{
    public function __construct(
        public string $amount,
        public int $paymentMethodId,
        public ?string $referenceNumber = null,
        public ?string $externalBankName = null,
        public ?string $externalBankBranch = null,
        public ?string $instrumentNumber = null,
        public ?string $instrumentDate = null,
    ) {}
}
