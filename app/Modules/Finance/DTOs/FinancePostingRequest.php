<?php

declare(strict_types=1);

namespace Modules\Finance\DTOs;

final readonly class FinancePostingRequest
{
    /**
     * @param  list<FinancePostingLine>  $lines
     */
    public function __construct(
        public PostingSourceData $source,
        public string $postingDate,
        public ?int $currencyId = null,
        public string $exchangeRate = '1.000000',
        public array $lines = [],
        public ?string $description = null,
    ) {}
}
