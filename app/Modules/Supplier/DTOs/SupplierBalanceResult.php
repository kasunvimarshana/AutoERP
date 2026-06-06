<?php

declare(strict_types=1);

namespace Modules\Supplier\DTOs;

final readonly class SupplierBalanceResult
{
    public function __construct(
        public int $supplierId,
        public string $openingBalance,
        public string $invoiceTotal,
        public string $paymentTotal,
        public string $creditTotal,
        public string $debitTotal,
        public string $outstandingBalance,
        public ?string $lastTransactionDate = null,
    ) {}
}
