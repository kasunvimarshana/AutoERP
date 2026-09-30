<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class VehicleServiceJobDiscountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'revision' => (int) $this->revision,
            'action' => $this->enum($this->action),
            'calculation_type' => $this->enum($this->calculation_type),
            'rate' => (string) $this->rate,
            'fixed_amount' => (string) $this->fixed_amount,
            'calculation_base' => (string) $this->calculation_base_snapshot,
            'calculated_amount' => (string) $this->calculated_amount_snapshot,
            'reason' => $this->reason,
            'changed_by' => $this->whenLoaded('changedBy', fn () => $this->changedBy === null ? null : [
                'id' => (int) $this->changedBy->getKey(),
                'code' => $this->changedBy->email,
                'name' => trim(($this->changedBy->first_name ?? '').' '.($this->changedBy->last_name ?? '')),
            ]),
            'changed_at' => $this->changed_at?->toISOString(),
        ];
    }

    private function enum(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }
}
