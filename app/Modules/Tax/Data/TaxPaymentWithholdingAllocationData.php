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

final readonly class TaxPaymentWithholdingAllocationData
{
    public function __construct(
        public TaxableDocumentData $invoice,
        public string $allocatedAmount,
        public string $invoiceTotal,
    ) {}
}
