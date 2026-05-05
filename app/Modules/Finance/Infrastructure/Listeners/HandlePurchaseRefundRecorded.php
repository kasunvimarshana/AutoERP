<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Listeners;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Finance\Application\Contracts\CreateApTransactionServiceInterface;
use Modules\Finance\Application\Contracts\CreateJournalEntryServiceInterface;
use Modules\Finance\Domain\RepositoryInterfaces\ApTransactionRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\FiscalPeriodRepositoryInterface;
use Modules\Finance\Infrastructure\Listeners\Concerns\HandlesReplayConflicts;
use Modules\Purchase\Domain\Events\PurchaseRefundRecorded;

class HandlePurchaseRefundRecorded
{
    use HandlesReplayConflicts;

    public function __construct(
        private readonly FiscalPeriodRepositoryInterface $fiscalPeriodRepository,
        private readonly CreateJournalEntryServiceInterface $createJournalEntryService,
        private readonly CreateApTransactionServiceInterface $createApTransactionService,
        private readonly ApTransactionRepositoryInterface $apTransactionRepository,
    ) {}

    public function handle(PurchaseRefundRecorded $event): void
    {
        if ($event->apAccountId === null) {
            Log::warning('HandlePurchaseRefundRecorded: AP account not configured; skipping journal entry', [
                'purchase_invoice_id' => $event->purchaseInvoiceId,
                'refund_payment_id'   => $event->refundPaymentId,
                'tenant_id'           => $event->tenantId,
            ]);

            return;
        }

        if (bccomp($event->amount, '0.000000', 6) <= 0) {
            Log::warning('HandlePurchaseRefundRecorded: zero or negative refund amount; skipping journal entry', [
                'purchase_invoice_id' => $event->purchaseInvoiceId,
                'refund_payment_id'   => $event->refundPaymentId,
            ]);

            return;
        }

        if ($this->artifactsAlreadyPosted($event->tenantId, 'purchase_refund', $event->refundPaymentId, 'ap_transactions')) {
            Log::info('HandlePurchaseRefundRecorded: replay detected; finance artifacts already exist, skipping', [
                'refund_payment_id' => $event->refundPaymentId,
                'tenant_id'         => $event->tenantId,
            ]);

            return;
        }

        $refundDate = $event->refundDate !== ''
            ? new \DateTimeImmutable($event->refundDate)
            : new \DateTimeImmutable;

        $period = $this->fiscalPeriodRepository->findOpenPeriodForDate($event->tenantId, $refundDate);
        if ($period === null) {
            Log::warning('HandlePurchaseRefundRecorded: no open fiscal period for refund date; skipping journal entry', [
                'purchase_invoice_id' => $event->purchaseInvoiceId,
                'refund_payment_id'   => $event->refundPaymentId,
                'refund_date'         => $event->refundDate,
                'tenant_id'           => $event->tenantId,
            ]);

            return;
        }

        $amount       = $event->amount;
        $exchangeRate = $event->exchangeRate;
        $baseAmount   = bcmul($amount, $exchangeRate, 6);
        $description  = 'Supplier refund for Purchase Invoice #'.$event->purchaseInvoiceId.' (Refund #'.$event->refundPaymentId.')';

        $jeLines = [
            // DR: Cash / Bank — money received back from supplier
            [
                'account_id'         => $event->cashAccountId,
                'debit_amount'       => $amount,
                'credit_amount'      => '0.000000',
                'description'        => $description,
                'currency_id'        => $event->currencyId,
                'exchange_rate'      => (float) $exchangeRate,
                'base_debit_amount'  => $baseAmount,
                'base_credit_amount' => '0.000000',
            ],
            // CR: Accounts Payable — liability restored (payment partially reversed)
            [
                'account_id'         => $event->apAccountId,
                'debit_amount'       => '0.000000',
                'credit_amount'      => $amount,
                'description'        => $description,
                'currency_id'        => $event->currencyId,
                'exchange_rate'      => (float) $exchangeRate,
                'base_debit_amount'  => '0.000000',
                'base_credit_amount' => $baseAmount,
            ],
        ];

        try {
            DB::transaction(function () use ($event, $period, $refundDate, $description, $jeLines, $amount): void {
                $this->createJournalEntryService->execute([
                    'tenant_id'        => $event->tenantId,
                    'fiscal_period_id' => $period->getId(),
                    'entry_date'       => $refundDate->format('Y-m-d'),
                    'created_by'       => $event->createdBy ?: 1,
                    'entry_type'       => 'system',
                    'reference_type'   => 'purchase_refund',
                    'reference_id'     => $event->refundPaymentId,
                    'description'      => $description,
                    'lines'            => $jeLines,
                ]);

                // Restore AP balance: refund increases what we still owe (or re-opens the payable)
                $currentBalance = (float) $this->apTransactionRepository
                    ->getSupplierBalance($event->tenantId, $event->supplierId);

                $newBalance = (float) bcadd((string) $currentBalance, $amount, 6);

                $this->createApTransactionService->execute([
                    'tenant_id'        => $event->tenantId,
                    'supplier_id'      => $event->supplierId,
                    'account_id'       => $event->apAccountId,
                    'transaction_type' => 'refund',
                    'amount'           => (float) $amount,
                    'balance_after'    => $newBalance,
                    'transaction_date' => $refundDate->format('Y-m-d'),
                    'currency_id'      => $event->currencyId,
                    'reference_type'   => 'purchase_refund',
                    'reference_id'     => $event->refundPaymentId,
                ]);
            });
        } catch (QueryException $exception) {
            if (! $this->isReplayConflict($exception, [
                'ap_transactions_tenant_reference_uk',
                'ap_transactions.tenant_id, ap_transactions.reference_type, ap_transactions.reference_id',
            ])) {
                throw $exception;
            }

            if (! $this->artifactsAlreadyPosted($event->tenantId, 'purchase_refund', $event->refundPaymentId, 'ap_transactions')) {
                throw new \RuntimeException(
                    'HandlePurchaseRefundRecorded: replay conflict detected with incomplete finance artifacts for refund_payment_id '.$event->refundPaymentId,
                    0,
                    $exception
                );
            }

            Log::info('HandlePurchaseRefundRecorded: duplicate-key replay conflict detected; skipping', [
                'refund_payment_id' => $event->refundPaymentId,
                'tenant_id'         => $event->tenantId,
            ]);
        }
    }
}
