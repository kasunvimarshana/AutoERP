<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Invoice\Data;

final readonly class InvoiceSourceLineSnapshot
{
    public function __construct(
        public string $sourceType,
        public int $sourceId,
        public string $sourceLineType,
        public int $sourceLineId,
        public string $invoicedQuantity,
    ) {}
}
