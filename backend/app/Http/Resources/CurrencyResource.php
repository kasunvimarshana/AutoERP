<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrencyResource extends JsonResource
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
            'code' => $this->code,
            'name' => $this->name,
            'symbol' => $this->symbol,
            'exchange_rate' => (float) $this->exchange_rate,
            'decimal_places' => $this->decimal_places,
            'is_active' => $this->is_active,
            'is_default' => $this->is_default,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            // Computed properties
            'formatted_symbol' => $this->symbol,
            'display_name' => "{$this->name} ({$this->code})",
            'rate_formatted' => number_format((float) $this->exchange_rate, 6),
        ];
    }
    
    /**
     * Format amount with currency.
     */
    public function formatAmount(float $amount): string
    {
        return $this->symbol . ' ' . number_format($amount, $this->decimal_places);
    }
}
