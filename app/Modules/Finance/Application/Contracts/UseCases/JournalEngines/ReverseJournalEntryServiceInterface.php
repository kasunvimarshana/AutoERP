<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\JournalEngines;

use Modules\Core\Application\Results\Result;

interface ReverseJournalEntryServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $journalEntryId, array $payload): Result;
}
