<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Results\Result;
use Modules\Finance\Application\Contracts\Services\FinancePostingServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\JournalEngines\PostJournalEntryServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\JournalEngines\ReverseJournalEntryServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\JournalEntries\CreateJournalEntryServiceInterface;

final class FinancePostingService implements FinancePostingServiceInterface
{
    public function __construct(
        private readonly CreateJournalEntryServiceInterface $createJournalEntryService,
        private readonly PostJournalEntryServiceInterface $postJournalEntryService,
        private readonly ReverseJournalEntryServiceInterface $reverseJournalEntryService,
    ) {
    }

    public function postFromSource(array $entryPayload, array $linesPayload): Result
    {
        $entryPayload['status'] = 'DRAFT';
        $entryPayload['lines'] = $linesPayload;

        $created = $this->createJournalEntryService->execute($entryPayload);
        if ($created->isFailure()) {
            return $created;
        }

        $entry = $created->valueOrFail();

        return $this->postJournalEntryService->execute((int) $entry->id(), [
            'posting_date' => $entryPayload['posting_date'] ?? $entryPayload['entry_date'] ?? now()->toDateString(),
            'posted_by' => $entryPayload['posted_by'] ?? null,
        ]);
    }

    public function reverseByEntryId(int|string $journalEntryId, array $payload = []): Result
    {
        return $this->reverseJournalEntryService->execute($journalEntryId, $payload);
    }
}
