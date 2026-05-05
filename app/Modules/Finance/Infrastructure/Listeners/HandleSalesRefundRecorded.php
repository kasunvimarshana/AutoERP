<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Listeners;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Finance\Application\Contracts\CreateArTransactionServiceInterface;
use Modules\Finance\Application\Contracts\CreateJournalEntryServiceInterface;
use Modules\Finance\Domain\RepositoryInterfaces\ArTransactionRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\FiscalPeriodRepositoryInterface;
use Modules\Finance\Infrastructure\Listeners\Concerns\HandlesReplayConflicts;
use Modules\Sales\Domain\Events\SalesRefundRecorded;

class HandleSalesRefundRecorded
{
    use HandlesReplayConflicts;

    public function __construct(
        private readonly FiscalPeriodRepositoryInterface $fiscalPeriodRepository,
        private readonly CreateJournalEntryServiceInterface $createJournalEntryService,
        private readonly CreateArTransactionServiceInterface $createArTransactionService,
        private readonly ArTransactionRepositoryInterface $arTransactionRepository,
    ) {}

    public function handle(SalesRefundRecorded $event): void
    {
        if ($event->arAccountId === null) {
            Log::warning('HandleSalesRefundRecorded: AR account not configured; skipping journal entry', [
                'sales_invoice_id' => $event->salesInvoiceId,
                'refund_payment_id' => $event->refundPaymentId,
                'tenant_id' => $event->tenantId,
            ]);

            return;
        }

        if (bccomp($event->amount, '0.000000', 6) <= 0) {
            Log::warning('HandleSalesRefundRecorded: zero or negative refund amount; skipping journal entry', [
                'sales_invoice_id' => $event->salesInvoiceId,
                'refund_payment_id' => $event->refundPaymentId,
            ]);

            return;
        }

        if ($this->artifactsAlreadyPosted($event->tenantId, 'sales_refund', $event->refundPaymentId, 'ar_transactions')) {
            Log::info('HandleSalesRefundRecorded: replay detected; finance artifacts already exist, skipping', [
                'refund_payment_id' => $event->refundPaymentId,
                'tenant_id' => $event->tenantId,
            ]);

            return;
        }

        $refundDate = $event->refundDate !== ''
            ? new \DateTimeImmutable($event->refundDate)
            : new \DateTimeImmutable;

        $period = $this->fiscalPeriodRepository->findOpenPeriodForDate($event->tenantId, $refundDate);
        if ($period === null) {
            Log::warning('HandleSalesRefundRecorded: no open fiscal period for refund date; skipping journal entry', [
                'sales_invoice_id' => $event->salesInvoiceId,
                'refund_payment_id' => $event->refundPaymentId,
                'refund_date' => $event->refundDate,
                'tenant_id' => $event->tenantId,
            ]);

            return;
        }

        $amount = $event->amount;
        $exchangeRate = $event->exchangeRate;
        $baseAmount = bcmul($amount, $exchangeRate, 6);
        $description = 'Refund issued for Sales Invoice #'.$event->salesInvoiceId.' (Refund #'.$event->refundPaymentId.')';

        $jeLines = [
            [
                'account_id' => $event->arAccountId,
                'debit_amount' => $amount,
                'credit_amount' => '0.000000',
                'description' => $description,
                'currency_id' => $event->currencyId,
                'exchange_rate' => (float) $exchangeRate,
                'base_debit_amount' => $baseAmount,
                'base_credit_amount' => '0.000000',
            ],
            [
                'account_id' => $event->cashAccountId,
                'debit_amount' => '0.000000',
                'credit_amount' => $amount,
                'description' => $description,
                'currency_id' => $event->currencyId,
                'exchange_rate' => (float) $exchangeRate,
                'base_debit_amount' => '0.000000',
                'base_credit_amount' => $baseAmount,
            ],
        ];

        try {
            DB::transaction(function () use ($event, $period, $refundDate, $description, $jeLines, $amount): void {
                $this->createJournalEntryService->execute([
                    'tenant_id' => $event->tenantId,
                    'fiscal_period_id' => $period->getId(),
                    'entry_date' => $refundDate->format('Y-m-d'),
                    'created_by' => $event->createdBy ?: 1,
                    'entry_type' => 'system',
                    'reference_type' => 'sales_refund',
                    'reference_id' => $event->refundPaymentId,
                    'description' => $description,
                    'lines' => $jeLines,
                ]);

                $currentBalance = $this->arTransactionRepository
                    ->getCustomerBalance($event->tenantId, $event->customerId);

                $newBalance = bcadd($currentBalance, $amount, 6);

                $this->createArTransactionService->execute([
                    'tenant_id' => $event->tenantId,
                    'customer_id' => $event->customerId,
                    'account_id' => $event->arAccountId,
                    'transaction_type' => 'refund',
                    'amount' => (float) $amount,
                    'balance_after' => (float) $newBalance,
                    'transaction_date' => $refundDate->format('Y-m-d'),
                    'currency_id' => $event->currencyId,
                    'reference_type' => 'sales_refund',
                    'reference_id' => $event->refundPaymentId,
                ]);
            });
        } catch (QueryException $exception) {
            if (! $this->isReplayConflict($exception, [
                'ar_transactions_tenant_reference_uk',
                'ar_transactions.tenant_id, ar_transactions.reference_type, ar_transactions.reference_id',
            ])) {
                throw $exception;
            }

            if (! $this->artifactsAlreadyPosted($event->tenantId, 'sales_refund', $event->refundPaymentId, 'ar_transactions')) {
                throw new \RuntimeException(
                    'HandleSalesRefundRecorded: replay conflict detected with incomplete finance artifacts for refund_payment_id '.$event->refundPaymentId,
                    0,
                    $exception
                );
            }

            Log::info('HandleSalesRefundRecorded: duplicate-key replay conflict detected; skipping', [
                'refund_payment_id' => $event->refundPaymentId,
                'tenant_id' => $event->tenantId,
            ]);
        }
    }
}
