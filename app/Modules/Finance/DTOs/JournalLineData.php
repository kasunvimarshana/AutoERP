<?php

declare(strict_types=1);

namespace Modules\Finance\DTOs;

final readonly class JournalLineData
{
    public function __construct(
        public int $accountId,
        public int $lineNumber,
        public string $debit = '0.000000',
        public string $credit = '0.000000',
        public ?string $description = null,
        public ?int $dimensionId = null,
        public ?string $sourceLineType = null,
        public ?int $sourceLineId = null,
    ) {}
}
