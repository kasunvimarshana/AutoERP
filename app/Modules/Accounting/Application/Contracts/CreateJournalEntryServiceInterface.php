<?php

declare(strict_types=1);

namespace Modules\Accounting\Application\Contracts;

use Modules\Accounting\Application\DTOs\CreateJournalEntryData;
use Modules\Accounting\Domain\Entities\JournalEntry;

interface CreateJournalEntryServiceInterface
{
    public function execute(CreateJournalEntryData $data): JournalEntry;
}
