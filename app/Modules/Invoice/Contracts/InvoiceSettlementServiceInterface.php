<?php

declare(strict_types=1);

namespace Modules\Invoice\Contracts;

use Modules\Core\DTOs\Integration\SettlementResultData;

interface InvoiceSettlementServiceInterface
{
    public function applyPaymentAllocation(int $invoiceId, string $amount, bool $allowOverpayment = false): SettlementResultData;

    public function reversePaymentAllocation(int $invoiceId, string $amount): SettlementResultData;
}
