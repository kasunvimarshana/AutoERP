<?php

declare(strict_types=1);

namespace Modules\Finance\DTOs;

use Modules\Finance\Enums\NormalBalance;

final readonly class AccountBalanceResult
{
    public function __construct(
        public int $accountId,
        public NormalBalance $normalBalance,
        public string $openingDebit,
        public string $openingCredit,
        public string $periodDebit,
        public string $periodCredit,
        public string $closingDebit,
        public string $closingCredit,
        public string $balance,
    ) {}
}
