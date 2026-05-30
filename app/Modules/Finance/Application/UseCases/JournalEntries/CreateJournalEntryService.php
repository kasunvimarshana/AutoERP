<?php

declare(strict_types=1);

namespace Modules\Finance\Application\UseCases\JournalEntries;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Finance\Application\Contracts\UseCases\JournalEntries\CreateJournalEntryServiceInterface;
use Modules\Finance\Application\Repositories\AccountRepositoryInterface;
use Modules\Finance\Application\Repositories\JournalEntryLineRepositoryInterface;
use Modules\Finance\Application\Repositories\JournalEntryRepositoryInterface;
use Modules\Finance\Domain\Constants\FinanceErrorCode;
use Modules\Finance\Domain\Constants\JournalEntryStatus;
use Throwable;

final class CreateJournalEntryService implements CreateJournalEntryServiceInterface
{
    public function __construct(
        private readonly JournalEntryRepositoryInterface $repository,
        private readonly JournalEntryLineRepositoryInterface $lineRepository,
        private readonly AccountRepositoryInterface $accountRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(array $payload): Result
    {
        try {
            $tenantId = (int) ($payload['tenant_id'] ?? 0);
            if ($tenantId <= 0) {
                return Result::failure(new Error(FinanceErrorCode::INVALID_VALUE, 'tenant_id is required.'));
            }

            $entryNumber = (string) ($payload['entry_number'] ?? '');
            if ($entryNumber === '') {
                return Result::failure(new Error(FinanceErrorCode::INVALID_VALUE, 'entry_number is required.'));
            }

            if ($this->repository->findByEntryNumber($tenantId, $entryNumber) !== null) {
                return Result::failure(new Error(FinanceErrorCode::CONFLICT, 'entry_number already exists in tenant.'));
            }

            $lines = is_array($payload['lines'] ?? null) ? $payload['lines'] : [];

            $entryPayload = $payload;
            unset($entryPayload['lines']);
            $entryPayload['status'] = strtoupper((string) ($entryPayload['status'] ?? JournalEntryStatus::DRAFT));

            if (! JournalEntryStatus::isValid($entryPayload['status'])) {
                return Result::failure(new Error(FinanceErrorCode::INVALID_VALUE, 'Invalid journal status.'));
            }

            $totals = $this->calculatePayloadTotals($lines);
            $entryPayload['total_debit'] = $totals['debit_total'];
            $entryPayload['total_credit'] = $totals['credit_total'];

            /** @var DataRecord $created */
            $created = $this->repository->transaction(function () use ($entryPayload, $tenantId, $lines): DataRecord {
                $created = $this->repository->create($entryPayload);

                if ($lines !== []) {
                    $lineNumber = 1;
                    foreach ($lines as $line) {
                        if (! is_array($line)) {
                            throw new \RuntimeException('Invalid journal line payload.');
                        }

                        $prepared = $this->prepareLinePayload($line, (int) $created->id(), $tenantId, $lineNumber++);
                        $this->lineRepository->create($prepared);
                    }
                }

                return $created;
            });

            return Result::success($created);
        } catch (Throwable $exception) {
            return Result::failure(new Error(FinanceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    /**
     * @param  array<string, mixed>  $line
     * @return array<string, mixed>
     */
    private function prepareLinePayload(array $line, int $journalEntryId, int $tenantId, int $lineNumber): array
    {
        $debit = (float) ($line['debit_amount'] ?? 0);
        $credit = (float) ($line['credit_amount'] ?? 0);

        if (($debit <= 0 && $credit <= 0) || ($debit > 0 && $credit > 0)) {
            throw new \RuntimeException('Each journal line must have either debit or credit amount.');
        }

        $accountId = (int) ($line['account_id'] ?? 0);
        if ($this->accountRepository->findPostableById($accountId, $tenantId) === null) {
            throw new \RuntimeException('Journal line account is not postable.');
        }

        $exchangeRate = (float) ($line['exchange_rate'] ?? 1.0);
        if ($exchangeRate <= 0) {
            throw new \RuntimeException('exchange_rate must be greater than zero.');
        }

        return [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $line['organization_unit_id'] ?? null,
            'metadata' => $line['metadata'] ?? null,
            'journal_entry_id' => $journalEntryId,
            'account_id' => $accountId,
            'description' => $line['description'] ?? null,
            'debit_amount' => $debit,
            'credit_amount' => $credit,
            'currency_id' => $line['currency_id'] ?? null,
            'exchange_rate' => $exchangeRate,
            'base_debit_amount' => isset($line['base_debit_amount'])
                ? (float) $line['base_debit_amount']
                : ($debit * $exchangeRate),
            'base_credit_amount' => isset($line['base_credit_amount'])
                ? (float) $line['base_credit_amount']
                : ($credit * $exchangeRate),
            'cost_center_id' => $line['cost_center_id'] ?? null,
            'party_type' => $line['party_type'] ?? null,
            'party_id' => $line['party_id'] ?? null,
            'tax_rate_id' => $line['tax_rate_id'] ?? null,
            'tax_amount' => (float) ($line['tax_amount'] ?? 0),
            'line_number' => $line['line_number'] ?? $lineNumber,
            'source_line_reference' => $line['source_line_reference'] ?? null,
        ];
    }

    /**
     * @param  array<int, mixed>  $lines
     * @return array<string, float>
     */
    private function calculatePayloadTotals(array $lines): array
    {
        $debit = 0.0;
        $credit = 0.0;

        foreach ($lines as $line) {
            if (! is_array($line)) {
                continue;
            }

            $exchangeRate = (float) ($line['exchange_rate'] ?? 1.0);
            $debit += isset($line['base_debit_amount'])
                ? (float) $line['base_debit_amount']
                : ((float) ($line['debit_amount'] ?? 0) * $exchangeRate);
            $credit += isset($line['base_credit_amount'])
                ? (float) $line['base_credit_amount']
                : ((float) ($line['credit_amount'] ?? 0) * $exchangeRate);
        }

        return [
            'debit_total' => round($debit, 4),
            'credit_total' => round($credit, 4),
        ];
    }
}
