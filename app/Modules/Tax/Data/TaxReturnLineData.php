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

final readonly class TaxReturnLineData
{
    public function __construct(
        public int $returnLineId,
        public string $sourceLineType,
        public ?int $sourceLineId,
        public string $returnedQuantity,
        public string $sourceQuantity,
    ) {}
}
