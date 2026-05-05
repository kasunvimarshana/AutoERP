<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\ConcurrentModificationException;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\Finance\Application\Contracts\UpdateJournalEntryServiceInterface;
use Modules\Finance\Application\DTOs\JournalEntryData;
use Modules\Finance\Domain\Entities\JournalEntry;
use Modules\Finance\Domain\Entities\JournalEntryLine;
use Modules\Finance\Domain\Exceptions\FiscalPeriodNotFoundException;
use Modules\Finance\Domain\Exceptions\JournalEntryNotFoundException;
use Modules\Finance\Domain\Exceptions\UnbalancedJournalEntryException;
use Modules\Finance\Domain\RepositoryInterfaces\FiscalPeriodRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\JournalEntryRepositoryInterface;

class UpdateJournalEntryService extends BaseService implements UpdateJournalEntryServiceInterface
{
    public function __construct(
        private readonly JournalEntryRepositoryInterface $journalEntryRepository,
        private readonly FiscalPeriodRepositoryInterface $fiscalPeriodRepository,
    ) {
        parent::__construct($journalEntryRepository);
    }

    protected function handle(array $data): JournalEntry
    {
        $id = (int) ($data['id'] ?? 0);
        $journalEntry = $this->journalEntryRepository->find($id);

        if (! $journalEntry) {
            throw new JournalEntryNotFoundException($id);
        }

        if (isset($data['row_version']) && (int) $data['row_version'] !== $journalEntry->getRowVersion()) {
            throw new ConcurrentModificationException('JournalEntry', $id);
        }

        if (! $journalEntry->isDraft()) {
            throw new DomainException('Only draft journal entries can be updated.');
        }

        $dto = JournalEntryData::fromArray($data);

        $fiscalPeriod = $this->fiscalPeriodRepository->find($dto->fiscal_period_id);
        if (! $fiscalPeriod || ! $fiscalPeriod->isOpen()) {
            throw FiscalPeriodNotFoundException::openPeriodForId($dto->fiscal_period_id);
        }

        $lines = [];
        $debitTotal = 0.0;
        $creditTotal = 0.0;

        foreach ($dto->lines as $lineDto) {
            $line = new JournalEntryLine(
                accountId: $lineDto->account_id,
                debitAmount: $lineDto->debit_amount,
                creditAmount: $lineDto->credit_amount,
                description: $lineDto->description,
                currencyId: $lineDto->currency_id,
                exchangeRate: $lineDto->exchange_rate,
                baseDebitAmount: $lineDto->base_debit_amount,
                baseCreditAmount: $lineDto->base_credit_amount,
                costCenterId: $lineDto->cost_center_id,
                metadata: $lineDto->metadata,
            );

            $debitTotal += $line->getDebitAmount();
            $creditTotal += $line->getCreditAmount();
            $lines[] = $line;
        }

        if (abs($debitTotal - $creditTotal) > PHP_FLOAT_EPSILON) {
            throw new UnbalancedJournalEntryException($debitTotal, $creditTotal);
        }

        $journalEntry->update(
            fiscalPeriodId: $dto->fiscal_period_id,
            entryType: $dto->entry_type,
            referenceType: $dto->reference_type,
            referenceId: $dto->reference_id,
            description: $dto->description,
            entryDate: new \DateTimeImmutable($dto->entry_date),
            postingDate: $dto->posting_date ? new \DateTimeImmutable($dto->posting_date) : null,
            lines: $lines,
        );

        return $this->journalEntryRepository->save($journalEntry);
    }
}
