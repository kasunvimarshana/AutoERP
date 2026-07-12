<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use Closure;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Enums\InvoiceBalanceStatus;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Models\InvoiceBalance;
use Modules\Invoice\Models\InvoiceCreditAllocation;

final class InvoiceBalanceUpdater
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly InvoiceBalanceCalculator $calculator,
        private readonly InvoiceStatusService $statuses,
    ) {}

    public function applyPayment(
        Invoice $invoice,
        string $amount,
        bool $allowOverpayment = false,
    ): InvoiceBalance {
        $this->assertPositive($amount, 'Payment amount');

        return $this->mutateBalance(
            $invoice,
            function (Invoice $lockedInvoice, InvoiceBalance $balance) use (
                $amount,
                $allowOverpayment,
            ): void {
                if (! $allowOverpayment
                    && $this->math->compare($amount, (string) $balance->remaining_amount) > 0
                ) {
                    throw new InvalidArgumentException('Paid amount cannot exceed invoice balance.');
                }

                $balance->paid_amount = $this->math->add(
                    (string) $balance->paid_amount,
                    $amount,
                );
            },
        );
    }

    public function reversePayment(Invoice $invoice, string $amount): InvoiceBalance
    {
        $this->assertPositive($amount, 'Payment reversal amount');

        return $this->mutateBalance(
            $invoice,
            function (Invoice $lockedInvoice, InvoiceBalance $balance) use ($amount): void {
                if ($this->math->compare($amount, (string) $balance->paid_amount) > 0) {
                    throw new InvalidArgumentException(
                        'Payment reversal amount cannot exceed paid invoice amount.',
                    );
                }

                $balance->paid_amount = $this->math->sub(
                    (string) $balance->paid_amount,
                    $amount,
                );
            },
        );
    }

    public function allocateCredit(
        Invoice $invoice,
        string $creditSourceType,
        int $creditSourceId,
        string $amount,
        bool $allowOverAllocation = false,
    ): InvoiceBalance {
        $this->assertPositive($amount, 'Credit allocation amount');

        return $this->mutateBalance(
            $invoice,
            function (Invoice $lockedInvoice, InvoiceBalance $balance) use (
                $creditSourceType,
                $creditSourceId,
                $amount,
                $allowOverAllocation,
            ): void {
                if (! $allowOverAllocation
                    && $this->math->compare($amount, (string) $balance->remaining_amount) > 0
                ) {
                    throw new InvalidArgumentException(
                        'Credit allocation cannot exceed invoice balance.',
                    );
                }

                $this->recordCreditAllocation(
                    $lockedInvoice,
                    $balance,
                    $creditSourceType,
                    $creditSourceId,
                    $amount,
                );

                $balance->credit_allocated_amount = $this->math->add(
                    (string) $balance->credit_allocated_amount,
                    $amount,
                );
            },
        );
    }

    public function cancel(Invoice $invoice): InvoiceBalance
    {
        return DB::transaction(function () use ($invoice): InvoiceBalance {
            $lockedInvoice = $this->lockInvoice($invoice);
            $status = $lockedInvoice->status instanceof InvoiceStatus
                ? $lockedInvoice->status
                : InvoiceStatus::from((string) $lockedInvoice->status);
            if (! in_array($status, [InvoiceStatus::Cancelled, InvoiceStatus::Void], true)) {
                throw new InvalidArgumentException(
                    'Invoice balance can only be cancelled after the invoice is cancelled or void.',
                );
            }

            $balance = $lockedInvoice->balance()->lockForUpdate()->firstOrFail();
            $balance->remaining_amount = '0.000000';
            $balance->status = $status === InvoiceStatus::Void
                ? InvoiceBalanceStatus::Void->value
                : InvoiceBalanceStatus::Cancelled->value;
            $balance->save();

            $lockedInvoice->forceFill([
                'balance_due' => '0.000000',
            ])->save();

            return $balance->refresh();
        });
    }

    public function reverse(Invoice $invoice): InvoiceBalance
    {
        return DB::transaction(function () use ($invoice): InvoiceBalance {
            $lockedInvoice = $this->lockInvoice($invoice);
            $status = $lockedInvoice->status instanceof InvoiceStatus
                ? $lockedInvoice->status
                : InvoiceStatus::from((string) $lockedInvoice->status);
            if ($status !== InvoiceStatus::Reversed) {
                throw new InvalidArgumentException(
                    'Invoice balance can only be reversed after the invoice is reversed.',
                );
            }

            $balance = $lockedInvoice->balance()->lockForUpdate()->firstOrFail();
            $balance->remaining_amount = '0.000000';
            $balance->status = InvoiceBalanceStatus::Reversed->value;
            $balance->save();

            $lockedInvoice->forceFill([
                'balance_due' => '0.000000',
            ])->save();

            return $balance->refresh();
        });
    }

    /**
     * @param  Closure(Invoice, InvoiceBalance): void  $mutation
     */
    private function mutateBalance(Invoice $invoice, Closure $mutation): InvoiceBalance
    {
        return DB::transaction(function () use ($invoice, $mutation): InvoiceBalance {
            $lockedInvoice = $this->lockInvoice($invoice);
            $this->statuses->assertCanSettle($lockedInvoice);
            $balance = $lockedInvoice->balance()->lockForUpdate()->firstOrFail();

            $mutation($lockedInvoice, $balance);

            return $this->syncBalance($lockedInvoice, $balance);
        });
    }

    private function syncBalance(Invoice $invoice, InvoiceBalance $balance): InvoiceBalance
    {
        $remaining = $this->calculator->remainingAmount(
            (string) $balance->invoice_total,
            (string) $balance->paid_amount,
            (string) $balance->credit_allocated_amount,
        );

        $balance->remaining_amount = $remaining;
        $balance->status = $this->calculator
            ->status((string) $balance->invoice_total, $remaining)
            ->value;
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

    private function recordCreditAllocation(
        Invoice $invoice,
        InvoiceBalance $balance,
        string $creditSourceType,
        int $creditSourceId,
        string $amount,
    ): void {
        $previouslyAllocated = $this->math->normalize((string) InvoiceCreditAllocation::query()
            ->where('tenant_id', $invoice->tenant_id)
            ->where('credit_source_type', $creditSourceType)
            ->where('credit_source_id', $creditSourceId)
            ->sum('allocated_amount'));

        InvoiceCreditAllocation::query()->create([
            'tenant_id' => $invoice->tenant_id,
            'organization_unit_id' => $invoice->organization_unit_id,
            'credit_source_type' => $creditSourceType,
            'credit_source_id' => $creditSourceId,
            'invoice_id' => $invoice->getKey(),
            'invoice_total' => $balance->invoice_total,
            'previously_allocated_amount' => $previouslyAllocated,
            'allocated_amount' => $amount,
            'remaining_invoice_balance' => $this->math->sub(
                (string) $balance->remaining_amount,
                $amount,
            ),
        ]);
    }

    private function lockInvoice(Invoice $invoice): Invoice
    {
        return Invoice::query()->lockForUpdate()->findOrFail($invoice->getKey());
    }

    private function assertPositive(string $amount, string $label): void
    {
        if ($this->math->isNegative($amount) || $this->math->isZero($amount)) {
            throw new InvalidArgumentException($label.' must be greater than zero.');
        }
    }
}
