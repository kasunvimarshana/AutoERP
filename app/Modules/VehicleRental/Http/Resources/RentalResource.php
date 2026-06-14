<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Modules\Core\Http\Resources\ModuleResource;

abstract class RentalResource extends ModuleResource
{
    protected function enum(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }

    protected function partySummary(mixed $party): ?array
    {
        if ($party === null) {
            return null;
        }

        return [
            'id' => (int) $party->getKey(),
            'code' => $party->code ?? $party->customer_number ?? $party->supplier_number,
            'name' => $party->display_name ?? $party->name,
        ];
    }

    protected function vehicleSummary(mixed $vehicle): ?array
    {
        if ($vehicle === null) {
            return null;
        }

        return [
            'id' => (int) $vehicle->getKey(),
            'code' => $vehicle->vehicle_number,
            'name' => $vehicle->registration_number ?? $vehicle->vehicle_number,
            'vehicle_number' => $vehicle->vehicle_number,
            'registration_number' => $vehicle->registration_number,
            'odometer_reading' => (string) $vehicle->odometer_reading,
            'status' => $this->enum($vehicle->status),
            'make' => $vehicle->relationLoaded('make') && $vehicle->make !== null
                ? ['id' => (int) $vehicle->make->getKey(), 'code' => $vehicle->make->code, 'name' => $vehicle->make->name]
                : null,
            'model' => $vehicle->relationLoaded('model') && $vehicle->model !== null
                ? ['id' => (int) $vehicle->model->getKey(), 'code' => $vehicle->model->code, 'name' => $vehicle->model->name]
                : null,
        ];
    }
}
