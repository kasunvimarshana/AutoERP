<?php

declare(strict_types=1);

namespace Modules\Transaction\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Transaction\Application\Contracts\CreateJournalEntryServiceInterface;
use Modules\Transaction\Application\DTOs\JournalEntryData;
use Modules\Transaction\Domain\Entities\JournalEntry;
use Modules\Transaction\Domain\Events\JournalEntryCreated;
use Modules\Transaction\Domain\RepositoryInterfaces\JournalEntryRepositoryInterface;

class CreateJournalEntryService extends BaseService implements CreateJournalEntryServiceInterface
{
    public function __construct(private readonly JournalEntryRepositoryInterface $journalEntryRepository)
    {
        parent::__construct($journalEntryRepository);
    }

    protected function handle(array $data): JournalEntry
    {
        $dto = JournalEntryData::fromArray($data);

        $journalEntry = new JournalEntry(
            tenantId:      $dto->tenantId,
            transactionId: $dto->transactionId,
            accountCode:   $dto->accountCode,
            accountName:   $dto->accountName,
            debitAmount:   $dto->debitAmount,
            creditAmount:  $dto->creditAmount,
            description:   $dto->description,
            status:        $dto->status,
            metadata:      $dto->metadata ? new Metadata($dto->metadata) : null,
        );

        $saved = $this->journalEntryRepository->save($journalEntry);
        $this->addEvent(new JournalEntryCreated($saved));

        return $saved;
    }
}
