<?php

declare(strict_types=1);

namespace Modules\Invoice\DTOs;

use Modules\Invoice\Enums\InvoiceBalanceStatus;

final readonly class InvoiceBalanceResult
{
    public function __construct(
        public int $invoiceId,
        public string $invoiceTotal,
        public string $paidAmount,
        public string $creditAmount,
        public string $remainingAmount,
        public InvoiceBalanceStatus $status,
    ) {}
}
