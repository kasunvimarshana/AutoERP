<?php

declare(strict_types=1);

namespace Modules\Financial\Infrastructure\Http\Resources;

use Modules\Core\Infrastructure\Http\Resources\BaseResource;

class JournalEntryResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'fiscal_year_id' => $this->fiscal_year_id,
            'entry_number'   => $this->entry_number,
            'entry_date'     => $this->entry_date?->toDateString(),
            'posting_date'   => $this->posting_date?->toDateString(),
            'type'           => $this->type,
            'status'         => $this->status,
            'description'    => $this->description,
            'reference'      => $this->reference,
            'currency_code'  => $this->currency_code,
            'exchange_rate'  => $this->exchange_rate,
            'total_debit'    => $this->total_debit,
            'total_credit'   => $this->total_credit,
            'posted_at'      => $this->posted_at?->toIso8601String(),
            'voided_at'      => $this->voided_at?->toIso8601String(),
            'void_reason'    => $this->void_reason,
            'lines'          => JournalEntryLineResource::collection($this->whenLoaded('lines')),
            'created_at'     => $this->created_at?->toIso8601String(),
            'updated_at'     => $this->updated_at?->toIso8601String(),
        ];
    }
}
