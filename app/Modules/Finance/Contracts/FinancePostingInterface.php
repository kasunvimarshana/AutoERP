<?php

declare(strict_types=1);

namespace Modules\Finance\Contracts;

use Modules\Finance\DTOs\PostingContext;
use Modules\Finance\DTOs\PostingResultData;

interface FinancePostingInterface
{
    public function createDraftJournal(PostingContext $request): PostingResultData;

    public function validatePosting(PostingContext $request): void;

    public function post(PostingContext $request, ?int $postedBy = null): PostingResultData;

    public function postJournal(int $journalId, ?int $postedBy = null): PostingResultData;

    public function reverseJournal(
        int $journalId,
        string $reversalDate,
        ?int $reversedBy = null,
        ?string $reason = null,
    ): PostingResultData;
}
