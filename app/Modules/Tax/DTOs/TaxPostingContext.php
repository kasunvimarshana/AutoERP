<?php

declare(strict_types=1);

namespace Modules\Tax\DTOs;

use Modules\Finance\DTOs\PostingContext;
use Modules\Finance\DTOs\PostingSourceData;

final readonly class TaxPostingContext
{
    /**
     * @param  list<TaxAmountData>  $taxLines
     */
    public function __construct(
        public PostingSourceData $source,
        public string $postingDate,
        public array $taxLines,
        public PostingContext $financeContext,
    ) {}
}
