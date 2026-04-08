<?php

declare(strict_types=1);

namespace Modules\Transaction\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Transaction\Application\Contracts\UpdateJournalEntryServiceInterface;
use Modules\Transaction\Application\DTOs\UpdateJournalEntryData;
use Modules\Transaction\Domain\Entities\JournalEntry;
use Modules\Transaction\Domain\Exceptions\JournalEntryNotFoundException;
use Modules\Transaction\Domain\RepositoryInterfaces\JournalEntryRepositoryInterface;

class UpdateJournalEntryService extends BaseService implements UpdateJournalEntryServiceInterface
{
    public function __construct(private readonly JournalEntryRepositoryInterface $journalEntryRepository)
    {
        parent::__construct($journalEntryRepository);
    }

    protected function handle(array $data): JournalEntry
    {
        $dto = UpdateJournalEntryData::fromArray($data);

        /** @var JournalEntry|null $journalEntry */
        $journalEntry = $this->journalEntryRepository->find($dto->id);
        if (! $journalEntry) {
            throw new JournalEntryNotFoundException($dto->id);
        }

        $journalEntry->updateDetails(
            accountCode:  $dto->accountCode ?? $journalEntry->getAccountCode(),
            accountName:  $dto->accountName ?? $journalEntry->getAccountName(),
            debitAmount:  $dto->debitAmount ?? $journalEntry->getDebitAmount(),
            creditAmount: $dto->creditAmount ?? $journalEntry->getCreditAmount(),
            description:  $dto->description ?? $journalEntry->getDescription(),
            metadata:     $dto->metadata ? new Metadata($dto->metadata) : null,
        );

        return $this->journalEntryRepository->save($journalEntry);
    }
}
