<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Listeners;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Finance\Application\Contracts\CreateJournalEntryServiceInterface;
use Modules\Finance\Domain\RepositoryInterfaces\FiscalPeriodRepositoryInterface;
use Modules\Finance\Infrastructure\Listeners\Concerns\HandlesReplayConflicts;
use Modules\ReturnRefund\Domain\Events\ReturnRefundDrafted;

class HandleReturnRefundDrafted
{
    use HandlesReplayConflicts;

    public function __construct(
        private readonly FiscalPeriodRepositoryInterface $fiscalPeriodRepository,
        private readonly CreateJournalEntryServiceInterface $createJournalEntryService,
    ) {
    }

    public function handle(ReturnRefundDrafted $event): void
    {
        if (bccomp($event->netRefundAmount, '0.000000', 6) <= 0) {
            Log::warning('HandleReturnRefundDrafted: zero or negative net refund amount; skipping journal entry', [
                'refund_id' => $event->refundId,
                'refund_number' => $event->refundNumber,
                'net_refund_amount' => $event->netRefundAmount,
            ]);

            return;
        }

        $tenantId = $this->parsePositiveInteger($event->tenantId);
        $referenceId = $this->parsePositiveInteger($event->refundId)
            ?? $this->parsePositiveInteger($event->rentalTransactionId);

        // Finance currently uses BIGINT tenant/reference IDs. UUID-only payloads are unsafe to post.
        if ($tenantId === null || $referenceId === null) {
            Log::warning('HandleReturnRefundDrafted: unsupported identifier format for Finance posting; skipping', [
                'tenant_id' => $event->tenantId,
                'refund_id' => $event->refundId,
                'rental_transaction_id' => $event->rentalTransactionId,
            ]);

            return;
        }

        if ($this->journalAlreadyPosted($tenantId, 'rental_refund', $referenceId)) {
            Log::info('HandleReturnRefundDrafted: replay detected; journal entry already exists, skipping', [
                'tenant_id' => $tenantId,
                'reference_id' => $referenceId,
            ]);

            return;
        }

        $postingDate = new \DateTimeImmutable();
        $period = $this->fiscalPeriodRepository->findOpenPeriodForDate($tenantId, $postingDate);

        if ($period === null) {
            Log::warning('HandleReturnRefundDrafted: no open fiscal period for posting date; skipping journal entry', [
                'tenant_id' => $tenantId,
                'refund_id' => $event->refundId,
                'refund_number' => $event->refundNumber,
            ]);

            return;
        }

        $arAccount = DB::table('accounts')
            ->where('tenant_id', $tenantId)
            ->where('sub_type', 'accounts_receivable')
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        $bankAccount = DB::table('bank_accounts')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        $cashAccountId = $bankAccount?->account_id;

        if ($cashAccountId === null) {
            $cashAccountId = DB::table('accounts')
                ->where('tenant_id', $tenantId)
                ->where('is_bank_account', true)
                ->where('is_active', true)
                ->orderBy('id')
                ->value('id');
        }

        if ($arAccount === null || $cashAccountId === null) {
            Log::warning('HandleReturnRefundDrafted: required AR or bank/cash account not found; skipping', [
                'tenant_id' => $tenantId,
                'ar_account_found' => $arAccount !== null,
                'cash_account_found' => $cashAccountId !== null,
            ]);

            return;
        }

        $amount = $event->netRefundAmount;
        $exchangeRate = '1.000000';
        $baseAmount = bcmul($amount, $exchangeRate, 6);
        $currencyId = (int) ($arAccount->currency_id ?? $bankAccount?->currency_id ?? 1);
        $description = 'Customer refund for Rental Refund #' . $event->refundNumber;

        $lines = [
            // DR: Accounts Receivable (reduce customer credit balance / refund liability)
            [
                'account_id' => (int) $arAccount->id,
                'debit_amount' => $amount,
                'credit_amount' => '0.000000',
                'description' => $description,
                'currency_id' => $currencyId,
                'exchange_rate' => (float) $exchangeRate,
                'base_debit_amount' => $baseAmount,
                'base_credit_amount' => '0.000000',
            ],
            // CR: Cash / Bank (cash out)
            [
                'account_id' => (int) $cashAccountId,
                'debit_amount' => '0.000000',
                'credit_amount' => $amount,
                'description' => $description,
                'currency_id' => $currencyId,
                'exchange_rate' => (float) $exchangeRate,
                'base_debit_amount' => '0.000000',
                'base_credit_amount' => $baseAmount,
            ],
        ];

        try {
            DB::transaction(function () use (
                $tenantId,
                $period,
                $postingDate,
                $referenceId,
                $description,
                $lines
            ): void {
                $this->createJournalEntryService->execute([
                    'tenant_id' => $tenantId,
                    'fiscal_period_id' => $period->getId(),
                    'entry_date' => $postingDate->format('Y-m-d'),
                    'created_by' => 1,
                    'entry_type' => 'system',
                    'reference_type' => 'rental_refund',
                    'reference_id' => $referenceId,
                    'description' => $description,
                    'lines' => $lines,
                ]);
            });
        } catch (QueryException $exception) {
            if (! $this->isReplayConflict($exception)) {
                throw $exception;
            }

            if (! $this->journalAlreadyPosted($tenantId, 'rental_refund', $referenceId)) {
                throw new \RuntimeException(
                    'HandleReturnRefundDrafted: replay conflict detected '
                    . 'with missing journal artifact for refund reference '
                    . $referenceId,
                    0,
                    $exception
                );
            }

            Log::info('HandleReturnRefundDrafted: duplicate-key replay conflict detected; skipping', [
                'tenant_id' => $tenantId,
                'reference_id' => $referenceId,
            ]);
        }
    }

    private function parsePositiveInteger(string $value): ?int
    {
        if (! ctype_digit($value)) {
            return null;
        }

        $intValue = (int) $value;

        return $intValue > 0 ? $intValue : null;
    }
}
