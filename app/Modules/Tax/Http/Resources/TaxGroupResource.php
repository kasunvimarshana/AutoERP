<?php

declare(strict_types=1);

namespace Modules\Tax\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TaxGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'organization_unit_id' => $this->organization_unit_id,
            'code' => $this->code,
            'name' => $this->name,
            'is_default' => (bool) $this->is_default,
            'active' => (bool) $this->active,
            'lines' => $this->whenLoaded('lines', fn () => $this->lines->map(fn ($line): array => [
                'id' => $line->id,
                'tax_id' => $line->tax_id,
                'sequence' => $line->sequence,
                'active' => (bool) $line->active,
                'tax' => $line->relationLoaded('tax') && $line->tax ? [
                    'id' => $line->tax->id,
                    'code' => $line->tax->code,
                    'name' => $line->tax->name,
                    'tax_type' => $line->tax->tax_type,
                ] : null,
            ])->values()->all()),
        ];
    }
}
