<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Customer\DTOs;

final readonly class CustomerCreditProfileData
{
    public function __construct(
        public ?string $creditLimit = null,
        public ?int $creditPeriodDays = null,
        public string $warningThresholdPercent = '80.000000',
        public bool $creditAllowed = true,
        public bool $advanceAllowed = true,
        public bool $allowOverCredit = false,
        public bool $allowPartialPayment = true,
        public bool $isActive = true,
        public ?int $rowVersion = null,
    ) {}
}
