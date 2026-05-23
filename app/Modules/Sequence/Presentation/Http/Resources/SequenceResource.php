<?php

declare(strict_types=1);

namespace Modules\Sequence\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SequenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'organization_unit_id' => $this->organization_unit_id,
            'document_type' => $this->document_type,
            'prefix' => $this->prefix,
            'suffix' => $this->suffix,
            'padding' => $this->padding,
            'next_number' => $this->next_number,
            'period_type' => $this->period_type,
            'period_value' => $this->period_value,
            'metadata' => $this->metadata,
            'row_version' => $this->row_version,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
