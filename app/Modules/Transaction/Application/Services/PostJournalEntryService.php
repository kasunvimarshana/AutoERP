<?php

declare(strict_types=1);

namespace Modules\Transaction\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Transaction\Application\Contracts\PostJournalEntryServiceInterface;
use Modules\Transaction\Domain\Entities\JournalEntry;
use Modules\Transaction\Domain\Events\JournalEntryPosted;
use Modules\Transaction\Domain\Exceptions\JournalEntryNotFoundException;
use Modules\Transaction\Domain\RepositoryInterfaces\JournalEntryRepositoryInterface;

class PostJournalEntryService extends BaseService implements PostJournalEntryServiceInterface
{
    public function __construct(private readonly JournalEntryRepositoryInterface $journalEntryRepository)
    {
        parent::__construct($journalEntryRepository);
    }

    protected function handle(array $data): JournalEntry
    {
        $id = $data['id'];

        /** @var JournalEntry|null $journalEntry */
        $journalEntry = $this->journalEntryRepository->find($id);
        if (! $journalEntry) {
            throw new JournalEntryNotFoundException($id);
        }

        $journalEntry->post();
        $saved = $this->journalEntryRepository->save($journalEntry);
        $this->addEvent(new JournalEntryPosted($saved));

        return $saved;
    }
}
