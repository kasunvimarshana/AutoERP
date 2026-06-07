<?php

declare(strict_types=1);

namespace Modules\Invoice\Contracts;

use Modules\Invoice\DTOs\BalanceResultData;
use Modules\Invoice\DTOs\InvoiceBalanceResult;

interface InvoiceBalanceProviderInterface
{
    /**
     * @param  list<int>  $invoiceIds
     * @return array<int, array{id: int, invoice_number: string|null, name: string}>
     */
    public function getInvoiceReferences(array $invoiceIds): array;

    public function getInvoiceBalance(int $invoiceId): InvoiceBalanceResult;

    public function getBalance(int $invoiceId): BalanceResultData;

    public function getInvoiceStatus(int $invoiceId): string;

    public function validatePayableState(int $invoiceId): BalanceResultData;
}
