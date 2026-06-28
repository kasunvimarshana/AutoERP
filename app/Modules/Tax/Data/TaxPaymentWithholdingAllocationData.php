<?php

declare(strict_types=1);

namespace Modules\Tax\Data;

final readonly class TaxPaymentWithholdingAllocationData
{
    public function __construct(
        public TaxableDocumentData $invoice,
        public string $allocatedAmount,
        public string $invoiceTotal,
    ) {}
}
