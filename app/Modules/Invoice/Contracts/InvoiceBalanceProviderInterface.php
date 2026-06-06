<?php

declare(strict_types=1);

namespace Modules\Invoice\Contracts;

use Modules\Core\DTOs\Integration\BalanceResultData;
use Modules\Invoice\DTOs\InvoiceBalanceResult;

interface InvoiceBalanceProviderInterface
{
    public function getInvoiceBalance(int $invoiceId): InvoiceBalanceResult;

    public function getBalance(int $invoiceId): BalanceResultData;

    public function getInvoiceStatus(int $invoiceId): string;

    public function validatePayableState(int $invoiceId): BalanceResultData;
}
