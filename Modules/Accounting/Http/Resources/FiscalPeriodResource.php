<?php

declare(strict_types=1);

namespace Modules\Accounting\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FiscalPeriodResource extends JsonResource
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
            'fiscal_year_id' => $this->fiscal_year_id,
            'name' => $this->name,
            'code' => $this->code,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'status' => $this->status->value,
            'closed_at' => $this->closed_at?->toISOString(),
            'closed_by' => $this->closed_by,
            'locked_at' => $this->locked_at?->toISOString(),
            'locked_by' => $this->locked_by,
            'is_open' => $this->isOpen(),
            'is_closed' => $this->isClosed(),
            'is_locked' => $this->isLocked(),
            'fiscal_year' => new FiscalYearResource($this->whenLoaded('fiscalYear')),
            'journal_entries_count' => $this->when(
                $this->relationLoaded('journalEntries'),
                fn () => $this->journalEntries->count()
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}
