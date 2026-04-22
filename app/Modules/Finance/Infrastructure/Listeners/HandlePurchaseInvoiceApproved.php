<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Finance\Application\Contracts\CreateJournalEntryServiceInterface;
use Modules\Finance\Domain\RepositoryInterfaces\FiscalPeriodRepositoryInterface;
use Modules\Purchase\Domain\Events\PurchaseInvoiceApproved;

class HandlePurchaseInvoiceApproved
{
    public function __construct(
        private readonly FiscalPeriodRepositoryInterface $fiscalPeriodRepository,
        private readonly CreateJournalEntryServiceInterface $createJournalEntryService,
    ) {}

    public function handle(PurchaseInvoiceApproved $event): void
    {
        if ($event->apAccountId === null) {
            Log::warning('HandlePurchaseInvoiceApproved: AP account not configured; skipping journal entry', [
                'purchase_invoice_id' => $event->purchaseInvoiceId,
                'tenant_id' => $event->tenantId,
            ]);

            return;
        }

        if (empty($event->lines)) {
            Log::warning('HandlePurchaseInvoiceApproved: no invoice lines in event; skipping journal entry', [
                'purchase_invoice_id' => $event->purchaseInvoiceId,
                'tenant_id' => $event->tenantId,
            ]);

            return;
        }

        // Aggregate debit amounts by account_id (inventory / expense accounts)
        $debitsByAccount = [];
        foreach ($event->lines as $line) {
            $accountId = isset($line['account_id']) ? (int) $line['account_id'] : null;
            if ($accountId === null) {
                Log::warning('HandlePurchaseInvoiceApproved: invoice line missing account_id; skipping journal entry', [
                    'purchase_invoice_id' => $event->purchaseInvoiceId,
                    'line' => $line,
                ]);

                return;
            }

            $amount = bcadd((string) ($line['line_total'] ?? '0'), (string) ($line['tax_amount'] ?? '0'), 6);
            $debitsByAccount[$accountId] = bcadd($debitsByAccount[$accountId] ?? '0.000000', $amount, 6);
        }

        $grandTotal = $event->grandTotal;
        $debitTotal = array_reduce(array_values($debitsByAccount), fn (string $carry, string $a): string => bcadd($carry, $a, 6), '0.000000');

        if (bccomp($debitTotal, $grandTotal, 2) !== 0) {
            Log::warning('HandlePurchaseInvoiceApproved: debit total does not match grand total; skipping journal entry', [
                'purchase_invoice_id' => $event->purchaseInvoiceId,
                'debit_total' => $debitTotal,
                'grand_total' => $grandTotal,
            ]);

            return;
        }

        $invoiceDate = $event->invoiceDate !== ''
            ? new \DateTimeImmutable($event->invoiceDate)
            : new \DateTimeImmutable;

        $period = $this->fiscalPeriodRepository->findOpenPeriodForDate($event->tenantId, $invoiceDate);
        if ($period === null) {
            Log::warning('HandlePurchaseInvoiceApproved: no open fiscal period for invoice date; skipping journal entry', [
                'purchase_invoice_id' => $event->purchaseInvoiceId,
                'invoice_date' => $event->invoiceDate,
                'tenant_id' => $event->tenantId,
            ]);

            return;
        }

        $exchangeRate = $event->exchangeRate;
        $description = 'AP entry for Purchase Invoice #'.$event->purchaseInvoiceId;

        $jeLines = [];
        foreach ($debitsByAccount as $accountId => $amount) {
            $baseAmount = bcmul($amount, $exchangeRate, 6);
            $jeLines[] = [
                'account_id' => $accountId,
                'debit_amount' => $amount,
                'credit_amount' => '0.000000',
                'description' => $description,
                'currency_id' => $event->currencyId,
                'exchange_rate' => (float) $exchangeRate,
                'base_debit_amount' => $baseAmount,
                'base_credit_amount' => '0.000000',
            ];
        }

        $baseGrandTotal = bcmul($grandTotal, $exchangeRate, 6);
        $jeLines[] = [
            'account_id' => $event->apAccountId,
            'debit_amount' => '0.000000',
            'credit_amount' => $grandTotal,
            'description' => $description,
            'currency_id' => $event->currencyId,
            'exchange_rate' => (float) $exchangeRate,
            'base_debit_amount' => '0.000000',
            'base_credit_amount' => $baseGrandTotal,
        ];

        DB::transaction(function () use ($event, $period, $invoiceDate, $description, $jeLines): void {
            $this->createJournalEntryService->execute([
                'tenant_id' => $event->tenantId,
                'fiscal_period_id' => $period->getId(),
                'entry_date' => $invoiceDate->format('Y-m-d'),
                'created_by' => $event->createdBy ?: 1,
                'entry_type' => 'system',
                'reference_type' => 'purchase_invoice',
                'reference_id' => $event->purchaseInvoiceId,
                'description' => $description,
                'lines' => $jeLines,
            ]);
        });
    }
}
