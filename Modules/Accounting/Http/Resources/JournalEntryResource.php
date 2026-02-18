<?php

declare(strict_types=1);

namespace Modules\Accounting\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'entry_number' => $this->entry_number,
            'entry_date' => $this->entry_date?->toDateString(),
            'reference' => $this->reference,
            'description' => $this->description,
            'fiscal_period_id' => $this->fiscal_period_id,
            'status' => $this->status,
            'total_debit' => $this->total_debit,
            'total_credit' => $this->total_credit,
            'currency' => $this->currency,
            'posted_at' => $this->posted_at?->toIso8601String(),
            'posted_by' => $this->posted_by,
            'is_balanced' => $this->isBalanced(),
            'tags' => $this->tags,
            'lines' => JournalEntryLineResource::collection($this->whenLoaded('lines')),
            'fiscal_period' => $this->whenLoaded('fiscalPeriod'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
