<?php

declare(strict_types=1);

namespace Modules\Finance\Application\UseCases\JournalEntries;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Finance\Application\Contracts\UseCases\JournalEntries\GetJournalEntryServiceInterface;
use Modules\Finance\Application\Repositories\AccountRepositoryInterface;
use Modules\Finance\Application\Repositories\JournalEntryLineRepositoryInterface;
use Modules\Finance\Application\Repositories\JournalEntryRepositoryInterface;
use Modules\Finance\Domain\Constants\FinanceErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class GetJournalEntryService implements GetJournalEntryServiceInterface
{
    public function __construct(
        private readonly JournalEntryRepositoryInterface $repository,
        private readonly JournalEntryLineRepositoryInterface $lineRepository,
        private readonly AccountRepositoryInterface $accountRepository,
    ) {}

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);
            if ($record === null) {
                return Result::failure(new Error(FinanceErrorCode::NOT_FOUND, 'JournalEntry not found.'));
            }

            $lines = array_map(
                fn (DataRecord $line): array => $this->decorateLine($line),
                $this->lineRepository->listByJournalEntry((int) $record->id()),
            );

            return Result::success(new DataRecord($record->toArray() + ['lines' => $lines]));
        } catch (Throwable $exception) {
            return Result::failure(new Error(FinanceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decorateLine(DataRecord $line): array
    {
        $values = $line->toArray();
        $tenantId = (int) ($values['tenant_id'] ?? 0);
        $accountId = (int) ($values['account_id'] ?? 0);
        $account = $tenantId > 0 && $accountId > 0
            ? $this->accountRepository->findById($accountId)
            : null;

        if ($account instanceof DataRecord && (int) $account->get('tenant_id', 0) === $tenantId) {
            $values['account_label'] = trim((string) $account->get('code') . ' - ' . (string) $account->get('name'));
        }

        return $values;
    }
}
