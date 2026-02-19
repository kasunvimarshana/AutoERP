<?php

declare(strict_types=1);

namespace Modules\Accounting\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalLineResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'journal_entry_id' => $this->journal_entry_id,
            'account_id' => $this->account_id,
            'line_number' => $this->line_number,
            'description' => $this->description,
            'debit' => $this->debit,
            'credit' => $this->credit,
            'amount' => $this->amount,
            'is_debit' => $this->isDebit(),
            'is_credit' => $this->isCredit(),
            'metadata' => $this->metadata,
            'account' => new AccountResource($this->whenLoaded('account')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
