<?php

declare(strict_types=1);

namespace Modules\Finance\Contracts;

use Modules\Core\DTOs\Integration\FinancePostingRequest;
use Modules\Core\DTOs\Integration\PostingResultData;

interface FinancePostingInterface
{
    public function createDraftJournal(FinancePostingRequest $request): PostingResultData;

    public function validatePosting(FinancePostingRequest $request): void;

    public function postJournal(int $journalId, ?int $postedBy = null): PostingResultData;

    public function reverseJournal(int $journalId, string $reversalDate, ?int $reversedBy = null): PostingResultData;
}
