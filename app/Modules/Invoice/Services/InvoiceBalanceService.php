<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use Modules\Invoice\DTOs\InvoiceBalanceResult;
use Modules\Invoice\Enums\InvoiceBalanceStatus;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Models\InvoiceBalance;

final class InvoiceBalanceService
{
    public function __construct(
        private readonly InvoiceBalanceCalculator $calculator,
        private readonly InvoiceBalanceUpdater $updater,
    ) {}

    public function createBalance(Invoice $invoice, string $invoiceTotal): InvoiceBalance
    {
        $balance = new InvoiceBalance();
        $balance->forceFill([
            'tenant_id' => $invoice->tenant_id,
            'organization_unit_id' => $invoice->organization_unit_id,
            'invoice_id' => $invoice->getKey(),
            'invoice_total' => $invoiceTotal,
            'paid_amount' => '0.000000',
            'credit_allocated_amount' => '0.000000',
            'debit_allocated_amount' => '0.000000',
            'refunded_amount' => '0.000000',
            'remaining_amount' => $invoiceTotal,
            'status' => $this->calculator->status($invoiceTotal, $invoiceTotal)->value,
        ]);
        $balance->save();

        return $balance;
    }

    public function result(InvoiceBalance $balance): InvoiceBalanceResult
    {
        return new InvoiceBalanceResult(
            invoiceId: (int) $balance->invoice_id,
            invoiceTotal: (string) $balance->invoice_total,
            paidAmount: (string) $balance->paid_amount,
            creditAmount: (string) $balance->credit_allocated_amount,
            debitAmount: (string) $balance->debit_allocated_amount,
            refundedAmount: (string) $balance->refunded_amount,
            remainingAmount: (string) $balance->remaining_amount,
            status: $balance->status instanceof InvoiceBalanceStatus
                ? $balance->status
                : InvoiceBalanceStatus::from((string) $balance->status),
        );
    }

    public function applyPayment(Invoice $invoice, string $amount, bool $allowOverpayment = false): InvoiceBalance
    {
        return $this->updater->applyPayment($invoice, $amount, $allowOverpayment);
    }

    public function reversePayment(Invoice $invoice, string $amount): InvoiceBalance
    {
        return $this->updater->reversePayment($invoice, $amount);
    }

    public function allocateCredit(
        Invoice $invoice,
        string $creditSourceType,
        int $creditSourceId,
        string $amount,
        bool $allowOverAllocation = false,
    ): InvoiceBalance {
        return $this->updater->allocateCredit(
            $invoice,
            $creditSourceType,
            $creditSourceId,
            $amount,
            $allowOverAllocation,
        );
    }

    public function cancel(Invoice $invoice): InvoiceBalance
    {
        return $this->updater->cancel($invoice);
    }

    public function reverse(Invoice $invoice): InvoiceBalance
    {
        return $this->updater->reverse($invoice);
    }
}
