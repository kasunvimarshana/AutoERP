<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Enums\InvoiceBalanceStatus;

final class InvoiceBalanceCalculator
{
    public function __construct(private readonly DecimalMath $math) {}

    public function remainingAmount(
        string $invoiceTotal,
        string $paidAmount,
        string $creditAllocatedAmount,
    ): string {
        return $this->math->sub(
            $this->math->sub($invoiceTotal, $paidAmount),
            $creditAllocatedAmount,
        );
    }

    public function status(string $invoiceTotal, string $remainingAmount): InvoiceBalanceStatus
    {
        if ($this->math->compare($remainingAmount, '0.000000') < 0) {
            return InvoiceBalanceStatus::Overpaid;
        }

        if ($this->math->isZero($remainingAmount)) {
            return InvoiceBalanceStatus::Paid;
        }

        if ($this->math->compare($remainingAmount, $invoiceTotal) < 0) {
            return InvoiceBalanceStatus::Partial;
        }

        return InvoiceBalanceStatus::Unpaid;
    }
}
