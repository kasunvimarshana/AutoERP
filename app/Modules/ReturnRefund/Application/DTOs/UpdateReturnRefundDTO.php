<?php

declare(strict_types=1);

namespace Modules\ReturnRefund\Application\DTOs;

class UpdateReturnRefundDTO
{
    public function __construct(
        public readonly ?string $endOdometer,
        public readonly ?string $actualDays,
        public readonly ?string $rentalCharge,
        public readonly ?string $extraCharges,
        public readonly ?string $damageCharges,
        public readonly ?string $fuelCharges,
        public readonly ?string $depositPaid,
        public readonly ?string $refundAmount,
        public readonly ?string $refundMethod,
        public readonly ?string $inspectionNotes,
        public readonly ?string $notes,
        public readonly ?array  $damagePhotos,
        public readonly ?array  $metadata,
    ) {}
}
