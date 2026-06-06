<?php

declare(strict_types=1);

namespace Modules\Supplier\DTOs;

final readonly class SupplierCreditProfileData
{
    public function __construct(
        public string $creditLimit = '0.000000',
        public ?int $creditPeriodDays = null,
        public string $warningThresholdPercent = '80.000000',
        public bool $allowOverCredit = false,
        public bool $allowPartialPayment = true,
        public bool $isActive = true,
    ) {}
}
