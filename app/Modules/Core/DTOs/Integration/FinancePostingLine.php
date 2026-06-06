<?php

declare(strict_types=1);

namespace Modules\Core\DTOs\Integration;

final readonly class FinancePostingLine
{
    public function __construct(
        public string $accountCode,
        public string $accountName,
        public string $debit = '0.000000',
        public string $credit = '0.000000',
        public ?string $description = null,
    ) {}
}
