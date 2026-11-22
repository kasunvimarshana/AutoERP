<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

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
        public ?string $cardBrand = null,
    ) {}
}
