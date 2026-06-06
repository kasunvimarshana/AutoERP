<?php

declare(strict_types=1);

namespace Modules\Customer\DTOs;

final readonly class CustomerBankAccountData
{
    public function __construct(
        public string $bankName,
        public string $accountName,
        public string $accountNumber,
        public ?string $branchName = null,
        public ?string $swiftCode = null,
        public ?string $iban = null,
        public ?int $currencyId = null,
        public bool $isPrimary = false,
        public bool $isActive = true,
        public ?string $notes = null,
    ) {}
}
