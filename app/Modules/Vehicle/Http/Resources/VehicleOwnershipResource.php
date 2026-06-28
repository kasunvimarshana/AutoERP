<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Vehicle\Http\Resources\Concerns\FormatsVehicleResources;
use Modules\Vehicle\Models\VehicleOwnership;

final class VehicleOwnershipResource extends JsonResource
{
    use FormatsVehicleResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'owner_type' => $this->owner_type,
            'owner_id' => $this->owner_id,
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
        return match ($this->owner_type) {
            VehicleOwnership::OWNER_TYPE_CUSTOMER => $this->relationLoaded('customerOwner') ? $this->namedResource($this->customerOwner, 'customer_number') : null,
            VehicleOwnership::OWNER_TYPE_SUPPLIER, VehicleOwnership::OWNER_TYPE_OWNER => $this->relationLoaded('supplierOwner') ? $this->namedResource($this->supplierOwner, 'supplier_number') : null,
            default => null,
        };
    }
}
