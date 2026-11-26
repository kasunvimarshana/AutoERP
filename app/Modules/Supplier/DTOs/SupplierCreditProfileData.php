<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Supplier\DTOs;

final readonly class SupplierCreditProfileData
{
    public function __construct(
        public ?string $creditLimit = null,
        public ?int $creditPeriodDays = null,
        public string $warningThresholdPercent = '80.000000',
        public ?bool $isCreditAllowed = null,
        public ?bool $isAdvanceAllowed = null,
        public bool $allowOverCredit = false,
        public bool $allowPartialPayment = true,
        public bool $isActive = true,
    ) {}
}
