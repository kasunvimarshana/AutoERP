<?php

declare(strict_types=1);

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
