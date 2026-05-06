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

        $fiscalPeriod = $this->fiscalPeriodRepository->find($dto->fiscalPeriodId);
        if (! $fiscalPeriod || ! $fiscalPeriod->isOpen()) {
            throw FiscalPeriodNotFoundException::openPeriodForId($dto->fiscalPeriodId);
        }

        $lines = [];
        $debitTotal = 0.0;
        $creditTotal = 0.0;

        foreach ($dto->lines as $lineDto) {
            $line = new JournalEntryLine(
                accountId: $lineDto->accountId,
                debitAmount: $lineDto->debitAmount,
                creditAmount: $lineDto->creditAmount,
                description: $lineDto->description,
                currencyId: $lineDto->currencyId,
                exchangeRate: $lineDto->exchangeRate,
                baseDebitAmount: $lineDto->baseDebitAmount,
                baseCreditAmount: $lineDto->baseCreditAmount,
                costCenterId: $lineDto->costCenterId,
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
            tenantId: $dto->tenantId,
            fiscalPeriodId: $dto->fiscalPeriodId,
            entryDate: new \DateTimeImmutable($dto->entryDate),
            createdBy: $dto->createdBy,
            lines: $lines,
            entryType: $dto->entryType,
            entryNumber: $dto->entryNumber,
            referenceType: $dto->referenceType,
            referenceId: $dto->referenceId,
            description: $dto->description,
            postingDate: $dto->postingDate ? new \DateTimeImmutable($dto->postingDate) : null,
            status: $dto->status,
            isReversed: $dto->isReversed,
            reversalEntryId: $dto->reversalEntryId,
            postedBy: $dto->postedBy,
            postedAt: $dto->postedAt ? new \DateTimeImmutable($dto->postedAt) : null,
        );

        return $this->journalEntryRepository->save($journalEntry);
    }
}
