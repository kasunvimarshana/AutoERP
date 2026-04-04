<?php
namespace Modules\Accounting\Application\Contracts;

use Modules\Accounting\Application\DTOs\JournalEntryData;
use Modules\Accounting\Domain\Entities\JournalEntry;

interface CreateJournalEntryServiceInterface
{
    public function execute(JournalEntryData $data): JournalEntry;
}
