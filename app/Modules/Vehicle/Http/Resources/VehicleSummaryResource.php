<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Vehicle\Http\Resources\Concerns\FormatsVehicleResources;

final class VehicleSummaryResource extends JsonResource
{
    use FormatsVehicleResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'row_version' => (int) $this->row_version,
            'vehicle_number' => $this->vehicle_number,
            'code' => $this->code,
            'registration_number' => $this->registration_number,
            'chassis_number' => $this->chassis_number,
            'engine_number' => $this->engine_number,
            'vin_number' => $this->vin_number,
            'make' => $this->relationLoaded('make') ? $this->namedResource($this->make) : null,
            'model' => $this->relationLoaded('model') ? $this->namedResource($this->model) : null,
            'type' => $this->relationLoaded('type') ? $this->namedResource($this->type) : null,
            'category' => $this->relationLoaded('category') ? $this->namedResource($this->category) : null,
            'current_ownerships' => $this->whenLoaded('currentOwnerships', fn () => VehicleOwnershipResource::collection($this->currentOwnerships)->resolve($request)),
            'current_customer' => $this->whenLoaded('currentOwnerships', fn () => $this->ownershipSnapshot('customer')),
            'current_supplier' => $this->whenLoaded('currentOwnerships', fn () => $this->ownershipSnapshot('supplier')),
            'status' => $this->enumValue($this->status),
            'odometer_reading' => (string) $this->odometer_reading,
            'odometer_unit' => $this->odometer_unit,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function ownershipSnapshot(string $ownerType): ?array
    {
        $ownership = $this->currentOwnerships->first(
            fn ($row): bool => ($row->owner_type instanceof \BackedEnum ? $row->owner_type->value : $row->owner_type) === $ownerType,
        );
        if ($ownership === null) {
            return null;
        }

        return [
            'id' => $ownership->owner_id === null ? null : (int) $ownership->owner_id,
            'code' => $ownership->owner_code_snapshot,
            'name' => $ownership->owner_name_snapshot,
        ];
    }
}
