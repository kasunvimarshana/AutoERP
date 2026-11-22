<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Tax\Data;

final readonly class TaxItemContext
{
    public function __construct(
        public int $itemId,
        public bool $isTaxExempt,
        public ?int $defaultTaxGroupId,
        public ?int $purchaseTaxGroupId,
        public ?int $salesTaxGroupId,
    ) {}
}
