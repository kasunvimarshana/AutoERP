<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\Finance\Application\Contracts\DeleteJournalEntryServiceInterface;
use Modules\Finance\Domain\Exceptions\JournalEntryNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\JournalEntryRepositoryInterface;

class DeleteJournalEntryService extends BaseService implements DeleteJournalEntryServiceInterface
{
    public function __construct(private readonly JournalEntryRepositoryInterface $journalEntryRepository)
    {
        parent::__construct($journalEntryRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $journalEntry = $this->journalEntryRepository->find($id);

        if (! $journalEntry) {
            throw new JournalEntryNotFoundException($id);
        }

        if (! $journalEntry->isDraft()) {
            throw new DomainException('Only draft journal entries can be deleted.');
        }

        return $this->journalEntryRepository->delete($id);
    }
}
