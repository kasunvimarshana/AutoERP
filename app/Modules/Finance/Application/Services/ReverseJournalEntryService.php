<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use DateTimeImmutable;
use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\Finance\Application\Contracts\ReverseJournalEntryServiceInterface;
use Modules\Finance\Domain\Entities\JournalEntry;
use Modules\Finance\Domain\Entities\JournalEntryLine;
use Modules\Finance\Domain\Exceptions\JournalEntryNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\JournalEntryRepositoryInterface;

class ReverseJournalEntryService extends BaseService implements ReverseJournalEntryServiceInterface
{
    public function __construct(private readonly JournalEntryRepositoryInterface $journalEntryRepository)
    {
        parent::__construct($journalEntryRepository);
    }

    protected function handle(array $data): JournalEntry
    {
        $id = (int) ($data['id'] ?? 0);
        $reversedBy = (int) ($data['reversed_by'] ?? 0);

        $original = $this->journalEntryRepository->find($id);
        if (! $original) {
            throw new JournalEntryNotFoundException($id);
        }

        if ($original->getStatus() !== 'posted') {
            throw new DomainException('Only posted journal entries can be reversed.');
        }

        if ($original->isReversed()) {
            throw new DomainException('This journal entry has already been reversed.');
        }

        // Build counter-entry lines (swap debit/credit)
        $reversalLines = array_map(
            static fn (JournalEntryLine $line): JournalEntryLine => new JournalEntryLine(
                accountId: $line->getAccountId(),
                debitAmount: $line->getCreditAmount(),
                creditAmount: $line->getDebitAmount(),
                description: $line->getDescription(),
                currencyId: $line->getCurrencyId(),
                exchangeRate: $line->getExchangeRate(),
                baseDebitAmount: $line->getBaseCreditAmount(),
                baseCreditAmount: $line->getBaseDebitAmount(),
                costCenterId: $line->getCostCenterId(),
                metadata: $line->getMetadata(),
            ),
            $original->getLines(),
        );

        $reversalEntry = new JournalEntry(
            tenantId: $original->getTenantId(),
            fiscalPeriodId: $original->getFiscalPeriodId(),
            entryDate: new DateTimeImmutable,
            createdBy: $reversedBy,
            entryType: 'reversal',
            referenceType: 'journal_entry',
            referenceId: $original->getId(),
            description: 'Reversal of journal entry #' . ($original->getEntryNumber() ?? (string) $original->getId()),
            status: 'posted',
            lines: $reversalLines,
        );

        $savedReversal = $this->journalEntryRepository->save($reversalEntry);

        $original->markReversed((int) $savedReversal->getId());
        $this->journalEntryRepository->save($original);

        return $savedReversal;
    }
}
