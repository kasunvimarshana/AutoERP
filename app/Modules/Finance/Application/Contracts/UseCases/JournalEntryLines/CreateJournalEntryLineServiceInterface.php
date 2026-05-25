<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\JournalEntryLines;

use Modules\Core\Application\Results\Result;

interface CreateJournalEntryLineServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}
