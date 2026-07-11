<?php

declare(strict_types=1);

namespace Modules\Customer\DTOs;

use Modules\Customer\Enums\CustomerStatus;
use Modules\Customer\Enums\CustomerType;

final readonly class CustomerResultData
{
    public function __construct(
        public int $customerId,
        public int $tenantId,
        public ?int $organizationUnitId,
        public string $customerNumber,
        public string $code,
        public string $name,
        public CustomerType $customerType,
        public CustomerStatus $status,
        public string $creditLimit,
        public bool $creditAllowed,
        public bool $advanceAllowed,
        public bool $isTaxExempt,
    ) {}
}
