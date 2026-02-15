<?php

declare(strict_types=1);

namespace App\Http\Resources\Taxation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxGroupRateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'rate' => $this->rate,
            'type' => $this->type,
            'sequence' => $this->pivot?->sequence,
            'apply_on_previous' => $this->pivot?->apply_on_previous,
            'is_active' => $this->pivot?->is_active ?? $this->is_active,
        ];
    }
}
