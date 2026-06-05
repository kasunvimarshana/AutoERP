<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class JournalEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'entry_number' => $this->resource->entry_number,
            'entry_type' => $this->resource->entry_type,
            'source_module' => $this->resource->source_module,
            'source_type' => $this->resource->source_type,
            'source_reference' => $this->resource->source_reference,
            'description' => $this->resource->description,
            'entry_date' => $this->resource->entry_date,
            'status' => $this->resource->status,
            'total_debit' => number_format((float) $this->resource->total_debit, 4, '.', ''),
            'total_credit' => number_format((float) $this->resource->total_credit, 4, '.', ''),
            'lines' => $this->when(isset($this->resource->lines), $this->resource->lines ?? []),
            'created_at' => $this->resource->created_at,
        ];
    }
}
