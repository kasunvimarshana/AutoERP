<?php

declare(strict_types=1);

namespace Modules\Finance\Application\UseCases\FinanceCore;

use Modules\Core\Application\Contracts\ErrorNormalizerInterface;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Finance\Application\Contracts\UseCases\FinanceCore\CloseFiscalPeriodServiceInterface;
use Modules\Finance\Application\Repositories\FiscalPeriodRepositoryInterface;
use Modules\Finance\Application\Repositories\JournalEntryRepositoryInterface;
use Modules\Finance\Domain\Constants\FinanceErrorCode;
use Modules\Finance\Domain\Constants\JournalEntryStatus;
use Throwable;

final class CloseFiscalPeriodService implements CloseFiscalPeriodServiceInterface
{
    public function __construct(
        private readonly FiscalPeriodRepositoryInterface $fiscalPeriods,
        private readonly JournalEntryRepositoryInterface $journalEntries,
        private readonly ErrorNormalizerInterface $errorNormalizer,
    ) {
    }

    public function execute(int|string $fiscalPeriodId, array $payload = []): Result
    {
        try {
            $period = $this->fiscalPeriods->findById($fiscalPeriodId);
            if (! $period instanceof DataRecord) {
                return Result::failure(new Error(FinanceErrorCode::NOT_FOUND, 'Fiscal period not found.'));
            }

            $currentStatus = strtoupper(trim((string) $period->get('status', 'OPEN')));
            if ($currentStatus !== 'OPEN') {
                return Result::failure(new Error(
                    FinanceErrorCode::FISCAL_PERIOD_NOT_OPEN,
                    'Fiscal period must be OPEN before closing.',
                    ['status' => $currentStatus],
                ));
            }

            if (
                $this->journalEntries->exists([
                    'fiscal_period_id' => (int) $period->id(),
                    'status' => JournalEntryStatus::DRAFT,
                ])
            ) {
                return Result::failure(new Error(
                    FinanceErrorCode::FISCAL_PERIOD_HAS_DRAFT_ENTRIES,
                    'Cannot close fiscal period with draft journal entries.',
                ));
            }

            $updated = $this->fiscalPeriods->update((int) $period->id(), [
                'status' => 'CLOSED',
                'metadata' => array_merge(
                    is_array($period->get('metadata')) ? $period->get('metadata') : [],
                    [
                        'closed_at' => now()->toISOString(),
                        'closed_by' => $payload['closed_by'] ?? null,
                    ],
                ),
                'row_version' => ((int) $period->get('row_version', 1)) + 1,
            ]);

            return Result::success($updated);
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                FinanceErrorCode::INVALID_VALUE,
                ['fiscal_period_id' => $fiscalPeriodId],
            ));
        }
    }
}
