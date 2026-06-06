<?php

declare(strict_types=1);

namespace Modules\Invoice\DTOs;

use Modules\Invoice\Enums\InvoiceBalanceStatus;

final readonly class InvoiceBalanceResult
{
    public function __construct(
        public string $invoiceTotal,
        public string $paidAmount,
        public string $creditAllocatedAmount,
        public string $debitAllocatedAmount,
        public string $refundedAmount,
        public string $remainingAmount,
        public InvoiceBalanceStatus $status,
    ) {}
}
