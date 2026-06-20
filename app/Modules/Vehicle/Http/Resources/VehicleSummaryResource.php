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
            'current_customer' => $this->whenLoaded('currentCustomerVehicles', fn () => $this->partyRelationship($this->currentCustomerVehicles->first(), 'customer')),
            'current_supplier' => $this->whenLoaded('currentSupplierVehicles', fn () => $this->partyRelationship($this->currentSupplierVehicles->first(), 'supplier')),
            'status' => $this->enumValue($this->status),
            'odometer_reading' => (string) $this->odometer_reading,
            'odometer_unit' => $this->odometer_unit,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function partyRelationship(mixed $relationship, string $party): ?array
    {
        if ($relationship === null) {
            return null;
        }
        $model = $relationship->{$party};

        return ['relationship_id' => (int) $relationship->getKey(), 'id' => (int) $model->getKey(), 'code' => $model->code, 'name' => $model->display_name ?? $model->name, 'started_at' => $relationship->started_at?->toISOString()];
    }
}
