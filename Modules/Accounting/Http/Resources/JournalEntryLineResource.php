<?php

declare(strict_types=1);

namespace Modules\Accounting\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalEntryLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'journal_entry_id' => $this->journal_entry_id,
            'account_id' => $this->account_id,
            'description' => $this->description,
            'debit_amount' => $this->debit_amount,
            'credit_amount' => $this->credit_amount,
            'currency' => $this->currency,
            'exchange_rate' => $this->exchange_rate,
            'reference' => $this->reference,
            'tags' => $this->tags,
            'account' => new AccountResource($this->whenLoaded('account')),
            'journal_entry' => new JournalEntryResource($this->whenLoaded('journalEntry')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
