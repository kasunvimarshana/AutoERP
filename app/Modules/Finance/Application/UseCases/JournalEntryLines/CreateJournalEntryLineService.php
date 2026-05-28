<?php

declare(strict_types=1);

namespace Modules\Finance\Application\UseCases\JournalEntryLines;

use Modules\Finance\Application\Contracts\UseCases\JournalEntryLines\CreateJournalEntryLineServiceInterface;
use Modules\Finance\Application\Repositories\AccountRepositoryInterface;
use Modules\Finance\Application\Repositories\JournalEntryRepositoryInterface;
use Modules\Finance\Application\Repositories\JournalEntryLineRepositoryInterface;
use Modules\Finance\Domain\Constants\FinanceErrorCode;
use Modules\Finance\Domain\Constants\JournalEntryStatus;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class CreateJournalEntryLineService implements CreateJournalEntryLineServiceInterface
{
    public function __construct(
        private readonly JournalEntryLineRepositoryInterface $repository,
        private readonly JournalEntryRepositoryInterface $entryRepository,
        private readonly AccountRepositoryInterface $accountRepository,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result
    {
        try {
            $entryId = (int) ($payload['journal_entry_id'] ?? 0);
            $entry = $this->entryRepository->findById($entryId);
            if ($entry === null) {
                return Result::failure(new Error(FinanceErrorCode::NOT_FOUND, 'Journal entry not found.'));
            }

            if (strtoupper((string) $entry->get('status', JournalEntryStatus::DRAFT)) !== JournalEntryStatus::DRAFT) {
                return Result::failure(new Error(
                    FinanceErrorCode::IMMUTABLE_POSTED_JOURNAL,
                    'Lines can only be modified in draft journal entries.',
                ));
            }

            $tenantId = (int) ($payload['tenant_id'] ?? $entry->get('tenant_id'));
            $debit = (float) ($payload['debit_amount'] ?? 0);
            $credit = (float) ($payload['credit_amount'] ?? 0);

            if (($debit <= 0 && $credit <= 0) || ($debit > 0 && $credit > 0)) {
                return Result::failure(new Error(
                    FinanceErrorCode::INVALID_JOURNAL_LINE,
                    'Each line requires either debit or credit amount.',
                ));
            }

            $accountId = (int) ($payload['account_id'] ?? 0);
            if ($this->accountRepository->findPostableById($accountId, $tenantId) === null) {
                return Result::failure(new Error(FinanceErrorCode::ACCOUNT_NOT_POSTABLE, 'Account is not postable.'));
            }

            if (! isset($payload['line_number'])) {
                $payload['line_number'] = $this->repository->nextLineNumber($tenantId, $entryId);
            }

            $exchangeRate = (float) ($payload['exchange_rate'] ?? 1.0);
            if ($exchangeRate <= 0) {
                return Result::failure(new Error(
                    FinanceErrorCode::INVALID_VALUE,
                    'exchange_rate must be greater than zero.',
                ));
            }

            $payload['exchange_rate'] = $exchangeRate;
            $payload['tenant_id'] = $tenantId;
            $payload['base_debit_amount'] = isset($payload['base_debit_amount'])
                ? (float) $payload['base_debit_amount']
                : ($debit * $exchangeRate);
            $payload['base_credit_amount'] = isset($payload['base_credit_amount'])
                ? (float) $payload['base_credit_amount']
                : ($credit * $exchangeRate);

            return Result::success($this->repository->create($payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(FinanceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
