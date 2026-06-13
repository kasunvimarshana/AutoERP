<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\Http\Resources\ModuleResource;

final class LedgerEntryResource extends ModuleResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'entry_date' => $this->entry_date?->toDateString(),
            'debit' => (string) $this->debit,
            'credit' => (string) $this->credit,
            'balance_after' => (string) $this->balance_after,
            'source_module' => $this->source_module,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'source_number' => $this->source_number,
            'source_date' => $this->source_date?->toDateString(),
            'account' => FinanceAccountSummaryResource::make($this->whenLoaded('account')),
            'journal_entry' => $this->whenLoaded('journalEntry'),
            'journal_line' => JournalLineResource::make($this->whenLoaded('journalLine')),
            'fiscal_period' => $this->whenLoaded('fiscalPeriod'),
            'dimension' => $this->whenLoaded('dimension'),
        ];
    }
}
