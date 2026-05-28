<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface FinancePostingServiceInterface
{
    /**
     * @param array<string, mixed> $entryPayload
     * @param list<array<string, mixed>> $linesPayload
     */
    public function postFromSource(array $entryPayload, array $linesPayload): Result;

    /** @param array<string, mixed> $payload */
    public function reverseByEntryId(int|string $journalEntryId, array $payload = []): Result;
}
