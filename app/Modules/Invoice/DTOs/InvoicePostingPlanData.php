<?php

declare(strict_types=1);

namespace Modules\Invoice\DTOs;

use Modules\Finance\Enums\FinancePostingProfileCode;

final readonly class InvoicePostingPlanData
{
    /** @param list<InvoicePostingLineData> $lines */
    public function __construct(
        public FinancePostingProfileCode $profile,
        public string $postingDate,
        public array $lines,
        public ?string $description = null,
    ) {}
}
