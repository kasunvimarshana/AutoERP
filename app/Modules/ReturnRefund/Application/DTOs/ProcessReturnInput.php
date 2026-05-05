<?php

declare(strict_types=1);

namespace Modules\ReturnRefund\Application\DTOs;

final class ProcessReturnInput
{
    public function __construct(
        public readonly string $tenantId,
        public readonly string $rentalTransactionId,
        public readonly string $grossAmount,
        public readonly bool $isDamaged,
        public readonly string $damageNotes,
        public readonly string $damageCharge,
        public readonly string $fuelAdjustmentCharge,
        public readonly string $lateReturnCharge,
    ) {
    }
}
