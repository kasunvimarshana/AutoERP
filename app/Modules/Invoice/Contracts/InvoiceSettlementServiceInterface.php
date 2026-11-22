<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Invoice\Contracts;

use Modules\Invoice\DTOs\SettlementResultData;

interface InvoiceSettlementServiceInterface
{
    public function applyPaymentAllocation(int $invoiceId, string $amount, bool $allowOverpayment = false): SettlementResultData;

    public function reversePaymentAllocation(int $invoiceId, string $amount): SettlementResultData;
}
