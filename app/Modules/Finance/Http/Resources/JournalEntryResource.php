<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\Http\Resources\ModuleResource;
use Modules\Finance\Enums\JournalStatus;

final class JournalEntryResource extends ModuleResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof JournalStatus
            ? $this->status
            : JournalStatus::from((string) $this->status);

        return [
            'id' => (int) $this->getKey(),
            'journal_number' => (string) $this->journal_number,
            'journal_date' => $this->journal_date?->toDateString(),
            'journal_type' => $this->enum($this->journal_type),
            'status' => $status->value,
            'description' => $this->description,
            'total_debit' => (string) $this->total_debit,
            'total_credit' => (string) $this->total_credit,
            'currency_id' => $this->currency_id,
            'exchange_rate' => (string) $this->exchange_rate,
            'source_module' => $this->source_module,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'source_number' => $this->source_number,
            'source_date' => $this->source_date?->toDateString(),
            'reversal_reason' => $this->reversal_reason,
            'posted_at' => $this->posted_at?->toIso8601String(),
            'reversed_at' => $this->reversed_at?->toIso8601String(),
            'fiscal_period' => $this->whenLoaded('fiscalPeriod'),
            'posting_profile' => $this->whenLoaded('postingProfile'),
            'lines' => JournalLineResource::collection($this->whenLoaded('lines')),
            'ledger_entries' => LedgerEntryResource::collection($this->whenLoaded('ledgerEntries')),
            'reversal_of' => $this->whenLoaded('reversalOf'),
            'reversals' => $this->whenLoaded('reversals'),
            'can_edit' => $status === JournalStatus::Draft,
            'can_post' => $status === JournalStatus::Draft,
            'can_cancel' => $status === JournalStatus::Draft,
            'can_reverse' => $status === JournalStatus::Posted
                && ($this->relationLoaded('reversals')
                    ? $this->reversals->isEmpty()
                    : ! $this->reversals()->exists()),
        ];
    }

    private function enum(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }
}
