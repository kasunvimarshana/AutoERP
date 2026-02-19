<?php

declare(strict_types=1);

namespace Modules\Accounting\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalEntryResource extends JsonResource
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
            'organization_id' => $this->organization_id,
            'fiscal_period_id' => $this->fiscal_period_id,
            'entry_number' => $this->entry_number,
            'entry_date' => $this->entry_date?->toDateString(),
            'reference' => $this->reference,
            'description' => $this->description,
            'status' => $this->status->value,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'posted_at' => $this->posted_at?->toISOString(),
            'posted_by' => $this->posted_by,
            'reversed_at' => $this->reversed_at?->toISOString(),
            'reversed_by' => $this->reversed_by,
            'reversal_entry_id' => $this->reversal_entry_id,
            'metadata' => $this->metadata,
            'total_debits' => $this->when(
                $this->relationLoaded('lines'),
                fn () => $this->total_debits
            ),
            'total_credits' => $this->when(
                $this->relationLoaded('lines'),
                fn () => $this->total_credits
            ),
            'is_balanced' => $this->when(
                $this->relationLoaded('lines'),
                fn () => $this->isBalanced()
            ),
            'is_posted' => $this->isPosted(),
            'is_draft' => $this->isDraft(),
            'is_reversed' => $this->isReversed(),
            'fiscal_period' => new FiscalPeriodResource($this->whenLoaded('fiscalPeriod')),
            'lines' => JournalLineResource::collection($this->whenLoaded('lines')),
            'lines_count' => $this->when(
                $this->relationLoaded('lines'),
                fn () => $this->lines->count()
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}
