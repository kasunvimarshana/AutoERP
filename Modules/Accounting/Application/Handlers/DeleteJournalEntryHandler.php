<?php

declare(strict_types=1);

namespace Modules\Accounting\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Modules\Accounting\Application\Commands\DeleteJournalEntryCommand;
use Modules\Accounting\Domain\Contracts\JournalEntryRepositoryInterface;
use Modules\Accounting\Domain\Enums\JournalEntryStatus;

class DeleteJournalEntryHandler extends BaseHandler
{
    public function __construct(
        private readonly JournalEntryRepositoryInterface $journalEntryRepository,
    ) {}

    public function handle(DeleteJournalEntryCommand $command): void
    {
        $this->transaction(function () use ($command): void {
            $entry = $this->journalEntryRepository->findById($command->id, $command->tenantId);

            if ($entry === null) {
                throw new \DomainException("Journal entry with ID {$command->id} not found.");
            }

            if ($entry->status !== JournalEntryStatus::Draft) {
                throw new \DomainException("Only draft journal entries can be deleted. Current status: {$entry->status->value}.");
            }

            $this->journalEntryRepository->delete($command->id, $command->tenantId);
        });
    }
}
