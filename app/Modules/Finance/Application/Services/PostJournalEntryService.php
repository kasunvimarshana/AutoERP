<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\Finance\Application\Contracts\PostJournalEntryServiceInterface;
use Modules\Finance\Domain\Entities\JournalEntry;
use Modules\Finance\Domain\Exceptions\JournalEntryNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\JournalEntryRepositoryInterface;

class PostJournalEntryService extends BaseService implements PostJournalEntryServiceInterface
{
    public function __construct(private readonly JournalEntryRepositoryInterface $journalEntryRepository)
    {
        parent::__construct($journalEntryRepository);
    }

    protected function handle(array $data): JournalEntry
    {
        $id = (int) ($data['id'] ?? 0);
        $postedBy = (int) ($data['posted_by'] ?? 0);

        $journalEntry = $this->journalEntryRepository->find($id);
        if (! $journalEntry) {
            throw new JournalEntryNotFoundException($id);
        }

        if (! $journalEntry->isDraft()) {
            throw new DomainException('Only draft journal entries can be posted.');
        }

        $postingDate = isset($data['posting_date']) ? new \DateTimeImmutable((string) $data['posting_date']) : null;

        $journalEntry->markPosted($postedBy, $postingDate);

        return $this->journalEntryRepository->save($journalEntry);
    }
}
