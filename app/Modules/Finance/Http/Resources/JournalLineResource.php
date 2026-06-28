<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class JournalLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'line_number' => (int) $this->line_number,
            'description' => $this->description,
            'debit' => (string) $this->debit,
            'credit' => (string) $this->credit,
            'source_line_type' => $this->source_line_type,
            'source_line_id' => $this->source_line_id,
            'account_id' => $this->account_id,
            'account' => FinanceAccountSummaryResource::make($this->whenLoaded('account')),
            'dimension' => $this->whenLoaded('dimension'),
        ];
    }
}
