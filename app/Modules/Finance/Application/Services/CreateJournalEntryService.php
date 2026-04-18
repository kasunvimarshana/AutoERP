<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\CreateJournalEntryServiceInterface;
use Modules\Finance\Application\DTOs\JournalEntryData;
use Modules\Finance\Domain\Entities\JournalEntry;
use Modules\Finance\Domain\Entities\JournalEntryLine;
use Modules\Finance\Domain\Exceptions\FiscalPeriodNotFoundException;
use Modules\Finance\Domain\Exceptions\UnbalancedJournalEntryException;
use Modules\Finance\Domain\RepositoryInterfaces\FiscalPeriodRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\JournalEntryRepositoryInterface;

class CreateJournalEntryService extends BaseService implements CreateJournalEntryServiceInterface
{
    public function __construct(
        private readonly JournalEntryRepositoryInterface $journalEntryRepository,
        private readonly FiscalPeriodRepositoryInterface $fiscalPeriodRepository,
    ) {
        parent::__construct($journalEntryRepository);
    }

    protected function handle(array $data): JournalEntry
    {
        $dto = JournalEntryData::fromArray($data);

        $fiscalPeriod = $this->fiscalPeriodRepository->find($dto->fiscal_period_id);
        if (! $fiscalPeriod || ! $fiscalPeriod->isOpen()) {
            throw new FiscalPeriodNotFoundException('Open fiscal period not found for provided fiscal_period_id.');
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

        $journalEntry = new JournalEntry(
            tenantId: $dto->tenant_id,
            fiscalPeriodId: $dto->fiscal_period_id,
            entryDate: new \DateTimeImmutable($dto->entry_date),
            createdBy: $dto->created_by,
            lines: $lines,
            entryType: $dto->entry_type,
            entryNumber: $dto->entry_number,
            referenceType: $dto->reference_type,
            referenceId: $dto->reference_id,
            description: $dto->description,
            postingDate: $dto->posting_date ? new \DateTimeImmutable($dto->posting_date) : null,
            status: $dto->status,
            isReversed: $dto->is_reversed,
            reversalEntryId: $dto->reversal_entry_id,
            postedBy: $dto->posted_by,
            postedAt: $dto->posted_at ? new \DateTimeImmutable($dto->posted_at) : null,
        );

        return $this->journalEntryRepository->save($journalEntry);
    }
}
