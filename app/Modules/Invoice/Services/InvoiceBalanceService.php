<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\DTOs\InvoiceBalanceResult;
use Modules\Invoice\Enums\InvoiceBalanceStatus;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Models\InvoiceBalance;
use Modules\Invoice\Models\InvoiceCreditAllocation;

final class InvoiceBalanceService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly InvoiceStatusService $statuses,
    ) {}

    public function createBalance(Invoice $invoice, string $invoiceTotal): InvoiceBalance
    {
        return InvoiceBalance::query()->create([
            'tenant_id' => $invoice->tenant_id,
            'organization_unit_id' => $invoice->organization_unit_id,
            'invoice_id' => $invoice->getKey(),
            'invoice_total' => $invoiceTotal,
            'paid_amount' => '0.000000',
            'credit_allocated_amount' => '0.000000',
            'debit_allocated_amount' => '0.000000',
            'refunded_amount' => '0.000000',
            'remaining_amount' => $invoiceTotal,
            'status' => $this->statusForRemaining($invoiceTotal, $invoiceTotal)->value,
        ]);
    }

    public function result(InvoiceBalance $balance): InvoiceBalanceResult
    {
        return new InvoiceBalanceResult(
            invoiceId: (int) $balance->invoice_id,
            invoiceTotal: (string) $balance->invoice_total,
            paidAmount: (string) $balance->paid_amount,
            creditAmount: (string) $balance->credit_allocated_amount,
            remainingAmount: (string) $balance->remaining_amount,
            status: $balance->status instanceof InvoiceBalanceStatus
                ? $balance->status
                : InvoiceBalanceStatus::from((string) $balance->status),
        );
    }

    public function applyPayment(Invoice $invoice, string $amount, bool $allowOverpayment = false): InvoiceBalance
    {
        $this->statuses->assertCanSettle($invoice);
        $this->assertPositive($amount, 'Payment amount');

        $balance = $invoice->balance()->lockForUpdate()->firstOrFail();
        if (! $allowOverpayment && $this->math->compare($amount, (string) $balance->remaining_amount) > 0) {
            throw new InvalidArgumentException('Paid amount cannot exceed invoice balance.');
        }

        $balance->paid_amount = $this->math->add((string) $balance->paid_amount, $amount);

        return $this->syncBalance($invoice, $balance);
    }

    public function reversePayment(Invoice $invoice, string $amount): InvoiceBalance
    {
        $this->statuses->assertCanSettle($invoice);
        $this->assertPositive($amount, 'Payment reversal amount');

        $balance = $invoice->balance()->lockForUpdate()->firstOrFail();
        if ($this->math->compare($amount, (string) $balance->paid_amount) > 0) {
            throw new InvalidArgumentException('Payment reversal amount cannot exceed paid invoice amount.');
        }

        $balance->paid_amount = $this->math->sub((string) $balance->paid_amount, $amount);

        return $this->syncBalance($invoice, $balance);
    }

    public function allocateCredit(
        Invoice $invoice,
        string $creditSourceType,
        int $creditSourceId,
        string $amount,
        bool $allowOverAllocation = false,
    ): InvoiceBalance {
        $this->statuses->assertCanSettle($invoice);
        $this->assertPositive($amount, 'Credit allocation amount');

        $balance = $invoice->balance()->lockForUpdate()->firstOrFail();
        if (! $allowOverAllocation && $this->math->compare($amount, (string) $balance->remaining_amount) > 0) {
            throw new InvalidArgumentException('Credit allocation cannot exceed invoice balance.');
        }

        $previouslyAllocated = $this->math->normalize((string) InvoiceCreditAllocation::query()
            ->where('tenant_id', $invoice->tenant_id)
            ->where('credit_source_type', $creditSourceType)
            ->where('credit_source_id', $creditSourceId)
            ->sum('allocated_amount'));

        $remainingAfterAllocation = $this->math->sub((string) $balance->remaining_amount, $amount);

        InvoiceCreditAllocation::query()->create([
            'tenant_id' => $invoice->tenant_id,
            'organization_unit_id' => $invoice->organization_unit_id,
            'credit_source_type' => $creditSourceType,
            'credit_source_id' => $creditSourceId,
            'invoice_id' => $invoice->getKey(),
            'invoice_total' => $balance->invoice_total,
            'previously_allocated_amount' => $previouslyAllocated,
            'allocated_amount' => $amount,
            'remaining_invoice_balance' => $remainingAfterAllocation,
        ]);

        $balance->credit_allocated_amount = $this->math->add((string) $balance->credit_allocated_amount, $amount);

        return $this->syncBalance($invoice, $balance);
    }

    public function cancel(Invoice $invoice): InvoiceBalance
    {
        $balance = $invoice->balance()->firstOrFail();
        $balance->remaining_amount = '0.000000';
        $balance->status = $invoice->status === InvoiceStatus::Void
            ? InvoiceBalanceStatus::Void->value
            : InvoiceBalanceStatus::Cancelled->value;
        $balance->save();

        $invoice->forceFill([
            'balance_due' => '0.000000',
        ])->save();

        return $balance->refresh();
    }

    private function syncBalance(Invoice $invoice, InvoiceBalance $balance): InvoiceBalance
    {
        $remaining = $this->remainingAmount(
            (string) $balance->invoice_total,
            (string) $balance->paid_amount,
            (string) $balance->credit_allocated_amount,
        );

        $balance->remaining_amount = $remaining;
        $balance->status = $this->statusForRemaining((string) $balance->invoice_total, $remaining)->value;
        $balance->save();

        $invoiceStatus = $invoice->status instanceof InvoiceStatus
            ? $invoice->status
            : InvoiceStatus::from((string) $invoice->status);
        $nextInvoiceStatus = $invoiceStatus;
        $balanceStatus = $balance->status instanceof InvoiceBalanceStatus
            ? $balance->status
            : InvoiceBalanceStatus::from((string) $balance->status);

        if ($balanceStatus === InvoiceBalanceStatus::Paid) {
            $nextInvoiceStatus = InvoiceStatus::Paid;
        } elseif ($balanceStatus === InvoiceBalanceStatus::Partial) {
            $nextInvoiceStatus = InvoiceStatus::PartiallyPaid;
        } elseif ($balanceStatus === InvoiceBalanceStatus::Unpaid
            && in_array($invoiceStatus, [InvoiceStatus::Paid, InvoiceStatus::PartiallyPaid], true)
        ) {
            $nextInvoiceStatus = InvoiceStatus::Posted;
        }

        $invoice->forceFill([
            'paid_total' => $balance->paid_amount,
            'credit_total' => $balance->credit_allocated_amount,
            'balance_due' => $remaining,
            'status' => $nextInvoiceStatus->value,
        ])->save();

        return $balance->refresh();
    }

    private function remainingAmount(string $invoiceTotal, string $paidAmount, string $creditAllocatedAmount): string
    {
        $remaining = $this->math->sub($invoiceTotal, $paidAmount);

        return $this->math->sub($remaining, $creditAllocatedAmount);
    }

    private function statusForRemaining(string $invoiceTotal, string $remaining): InvoiceBalanceStatus
    {
        if ($this->math->compare($remaining, '0.000000') < 0) {
            return InvoiceBalanceStatus::Overpaid;
        }

        if ($this->math->isZero($remaining)) {
            return InvoiceBalanceStatus::Paid;
        }

        if ($this->math->compare($remaining, $invoiceTotal) < 0) {
            return InvoiceBalanceStatus::Partial;
        }

        return InvoiceBalanceStatus::Unpaid;
    }

    private function assertPositive(string $amount, string $label): void
    {
        if ($this->math->isNegative($amount) || $this->math->isZero($amount)) {
            throw new InvalidArgumentException($label.' must be greater than zero.');
        }
    }
}
