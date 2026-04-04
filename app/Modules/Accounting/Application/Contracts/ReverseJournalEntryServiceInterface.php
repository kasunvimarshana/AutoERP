<?php
namespace Modules\Accounting\Application\Contracts;

use Modules\Accounting\Domain\Entities\JournalEntry;

interface ReverseJournalEntryServiceInterface
{
    public function execute(JournalEntry $entry, int $reversedBy): JournalEntry;
}
