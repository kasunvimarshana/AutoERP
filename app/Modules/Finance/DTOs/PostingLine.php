<?php

declare(strict_types=1);

namespace Modules\Finance\DTOs;

readonly class PostingLine
{
    public function __construct(
        public ?string $accountCode,
        public string $accountName,
        public string $debit = '0.000000',
        public string $credit = '0.000000',
        public ?string $description = null,
        public ?string $profileKey = null,
        public ?string $dimensionCode = null,
        public ?string $sourceLineType = null,
        public ?int $sourceLineId = null,
    ) {}
}
