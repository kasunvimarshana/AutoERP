<?php

declare(strict_types=1);

namespace Modules\Accounting\Interfaces\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Accounting\Domain\Entities\JournalEntryLine;

class JournalEntryLineResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var JournalEntryLine $line */
        $line = $this->resource;

        return [
            'id' => $line->id,
            'journal_entry_id' => $line->journalEntryId,
            'account_id' => $line->accountId,
            'account_code' => $line->accountCode,
            'account_name' => $line->accountName,
            'description' => $line->description,
            'debit_amount' => $line->debitAmount,
            'credit_amount' => $line->creditAmount,
        ];
    }
}
