<?php

declare(strict_types=1);

namespace Modules\Tax\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TaxPostingProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'organization_unit_id' => $this->organization_unit_id,
            'tax_id' => $this->tax_id,
            'tax' => $this->whenLoaded('tax', fn () => $this->tax ? [
                'id' => $this->tax->id,
                'code' => $this->tax->code,
                'name' => $this->tax->name,
                'tax_type' => $this->tax->tax_type,
            ] : null),
            'direction' => $this->direction,
            'posting_key' => $this->posting_key,
            'active' => (bool) $this->active,
        ];
    }
}
