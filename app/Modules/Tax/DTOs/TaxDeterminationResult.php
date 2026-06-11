<?php

declare(strict_types=1);

namespace Modules\Tax\DTOs;

final readonly class TaxDeterminationResult
{
    /**
     * @param  list<ApplicableTaxData>  $taxes
     */
    public function __construct(
        public ?int $taxGroupId,
        public array $taxes,
        public string $exemptionStatus = 'taxable',
    ) {}
}
