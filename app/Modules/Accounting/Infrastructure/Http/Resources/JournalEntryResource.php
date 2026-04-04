<?php
namespace Modules\Accounting\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'tenant_id'        => $this->tenant_id,
            'reference_number' => $this->reference_number,
            'status'           => $this->status,
            'entry_date'       => $this->entry_date instanceof \Carbon\Carbon
                                      ? $this->entry_date->toDateString()
                                      : $this->entry_date,
            'description'      => $this->description,
            'source_type'      => $this->source_type,
            'source_id'        => $this->source_id,
            'posted_by'        => $this->posted_by,
            'posted_at'        => $this->posted_at?->toIso8601String(),
            'reversed_by'      => $this->reversed_by,
            'reversed_at'      => $this->reversed_at?->toIso8601String(),
            'lines'            => $this->whenLoaded('lines', fn() =>
                $this->lines->map(fn($line) => [
                    'id'          => $line->id,
                    'account_id'  => $line->account_id,
                    'debit'       => $line->debit,
                    'credit'      => $line->credit,
                    'currency'    => $line->currency,
                    'description' => $line->description,
                ])->all()
            ),
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }
}
