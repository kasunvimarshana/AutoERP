<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
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
            'code' => $this->code ?? null,
            'name' => $this->name ?? null,
            'iso2' => $this->iso2 ?? null,
            'iso3' => $this->iso3 ?? null,
            'phone_code' => $this->phone_code ?? null,
            'capital' => $this->capital ?? null,
            'currency' => $this->currency ?? null,
            'currency_symbol' => $this->currency_symbol ?? null,
            'native' => $this->native ?? null,
            'region' => $this->region ?? null,
            'subregion' => $this->subregion ?? null,
            'latitude' => $this->latitude ? (float) $this->latitude : null,
            'longitude' => $this->longitude ? (float) $this->longitude : null,
            'emoji' => $this->emoji ?? null,
            'is_active' => $this->is_active ?? true,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            // Computed properties
            'display_name' => $this->name ?? 'Unknown',
            'flag' => $this->emoji ?? '',
        ];
    }
}
