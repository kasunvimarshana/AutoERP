<?php
namespace Modules\Accounting\Application\Contracts;

use Modules\Accounting\Domain\Entities\JournalEntry;

interface PostJournalEntryServiceInterface
{
    public function execute(JournalEntry $entry, int $postedBy): JournalEntry;
}
