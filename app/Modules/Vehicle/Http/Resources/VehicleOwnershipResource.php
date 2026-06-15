<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Vehicle\Http\Resources\Concerns\FormatsVehicleResources;

final class VehicleOwnershipResource extends JsonResource
{
    use FormatsVehicleResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'owner_type' => $this->owner_type,
            'owner_id' => $this->owner_id,
            'customer' => $this->relationLoaded('customer') ? $this->namedResource($this->customer, 'customer_number') : null,
            'owner' => $this->ownerResource(),
            'ownership_type' => $this->enumValue($this->ownership_type),
            'started_at' => $this->started_at?->toISOString(),
            'ended_at' => $this->ended_at?->toISOString(),
            'is_current' => (bool) $this->is_current,
            'notes' => $this->notes,
        ];
    }

    private function ownerResource(): ?array
    {
        $ownerType = $this->enumValue($this->owner_type);
        if ($ownerType === 'customer' && $this->relationLoaded('customer')) {
            return $this->namedResource($this->customer, 'customer_number');
        }
        if (in_array($ownerType, ['supplier', 'third_party'], true) && $this->relationLoaded('supplier')) {
            return $this->namedResource($this->supplier, 'supplier_number');
        }

        return $ownerType === 'company' ? ['id' => null, 'name' => 'Company'] : null;
    }
}
