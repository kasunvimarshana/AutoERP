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
use Modules\Purchase\Domain\Events\PurchasePaymentRecorded;

class HandlePurchasePaymentRecorded
{
    use HandlesReplayConflicts;

    public function __construct(
        private readonly FiscalPeriodRepositoryInterface $fiscalPeriodRepository,
        private readonly CreateJournalEntryServiceInterface $createJournalEntryService,
        private readonly CreateApTransactionServiceInterface $createApTransactionService,
        private readonly ApTransactionRepositoryInterface $apTransactionRepository,
    ) {}

    public function handle(PurchasePaymentRecorded $event): void
    {
        if ($event->apAccountId === null) {
            Log::warning('HandlePurchasePaymentRecorded: AP account not configured; skipping journal entry', [
                'purchase_invoice_id' => $event->purchaseInvoiceId,
                'payment_id' => $event->paymentId,
                'tenant_id' => $event->tenantId,
            ]);

            return;
        }

        if (bccomp($event->amount, '0.000000', 6) <= 0) {
            Log::warning('HandlePurchasePaymentRecorded: zero or negative payment amount; skipping journal entry', [
                'purchase_invoice_id' => $event->purchaseInvoiceId,
                'payment_id' => $event->paymentId,
            ]);

            return;
        }

        if ($this->artifactsAlreadyPosted($event->tenantId, 'purchase_payment', $event->paymentId, 'ap_transactions')) {
            Log::info('HandlePurchasePaymentRecorded: replay detected; finance artifacts already exist, skipping', [
                'payment_id' => $event->paymentId,
                'tenant_id' => $event->tenantId,
            ]);

            return;
        }

        $paymentDate = $event->paymentDate !== ''
            ? new \DateTimeImmutable($event->paymentDate)
            : new \DateTimeImmutable;

        $period = $this->fiscalPeriodRepository->findOpenPeriodForDate($event->tenantId, $paymentDate);
        if ($period === null) {
            Log::warning('HandlePurchasePaymentRecorded: no open fiscal period for payment date; skipping journal entry', [
                'purchase_invoice_id' => $event->purchaseInvoiceId,
                'payment_id' => $event->paymentId,
                'payment_date' => $event->paymentDate,
                'tenant_id' => $event->tenantId,
            ]);

            return;
        }

        $amount = $event->amount;
        $exchangeRate = $event->exchangeRate;
        $baseAmount = bcmul($amount, $exchangeRate, 6);
        $description = 'Payment for Purchase Invoice #'.$event->purchaseInvoiceId.' (Payment #'.$event->paymentId.')';

        $jeLines = [
            // DR: Accounts Payable — reduces the liability owed to the supplier
            [
                'account_id' => $event->apAccountId,
                'debit_amount' => $amount,
                'credit_amount' => '0.000000',
                'description' => $description,
                'currency_id' => $event->currencyId,
                'exchange_rate' => (float) $exchangeRate,
                'base_debit_amount' => $baseAmount,
                'base_credit_amount' => '0.000000',
            ],
            // CR: Cash / Bank Account — reduces the bank balance
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
            DB::transaction(function () use ($event, $period, $paymentDate, $description, $jeLines, $amount): void {
                $this->createJournalEntryService->execute([
                    'tenant_id' => $event->tenantId,
                    'fiscal_period_id' => $period->getId(),
                    'entry_date' => $paymentDate->format('Y-m-d'),
                    'created_by' => $event->createdBy ?: 1,
                    'entry_type' => 'system',
                    'reference_type' => 'purchase_payment',
                    'reference_id' => $event->paymentId,
                    'description' => $description,
                    'lines' => $jeLines,
                ]);

                // Record AP payment transaction (reduces amount owed to supplier)
                $currentBalance = (float) $this->apTransactionRepository
                    ->getSupplierBalance($event->tenantId, $event->supplierId);

                $newBalance = (float) bcsub((string) $currentBalance, $amount, 6);

                $this->createApTransactionService->execute([
                    'tenant_id' => $event->tenantId,
                    'supplier_id' => $event->supplierId,
                    'account_id' => $event->apAccountId,
                    'transaction_type' => 'payment',
                    'amount' => -1 * (float) $amount,
                    'balance_after' => $newBalance,
                    'transaction_date' => $paymentDate->format('Y-m-d'),
                    'currency_id' => $event->currencyId,
                    'reference_type' => 'purchase_payment',
                    'reference_id' => $event->paymentId,
                ]);
            });
        } catch (QueryException $exception) {
            if (! $this->isReplayConflict($exception, [
                'ap_transactions_tenant_reference_uk',
                'ap_transactions.tenant_id, ap_transactions.reference_type, ap_transactions.reference_id',
            ])) {
                throw $exception;
            }

            if (! $this->artifactsAlreadyPosted($event->tenantId, 'purchase_payment', $event->paymentId, 'ap_transactions')) {
                throw new \RuntimeException(
                    'HandlePurchasePaymentRecorded: replay conflict detected with incomplete finance artifacts for payment_id '.$event->paymentId,
                    0,
                    $exception
                );
            }

            Log::info('HandlePurchasePaymentRecorded: duplicate-key replay conflict detected; skipping', [
                'payment_id' => $event->paymentId,
                'tenant_id' => $event->tenantId,
            ]);
        }
    }
}
