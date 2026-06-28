<?php

declare(strict_types=1);

namespace Modules\Vehicle\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Vehicle\DTOs\UpdateVehicleData;
use Modules\Vehicle\Enums\VehicleStatus;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Validators\VehicleValidationService;
use Throwable;

final class VehicleUpdateService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly VehicleValidationService $validator,
        private readonly VehicleDocumentService $documents,
        private readonly VehicleOwnershipService $ownerships,
        private readonly VehicleAttributeService $attributes,
    ) {}

    public function update(Vehicle $vehicle, UpdateVehicleData $data): Vehicle
    {
        if (in_array($vehicle->status, [VehicleStatus::Sold, VehicleStatus::Scrapped], true)) {
            throw new InvalidArgumentException('Sold or scrapped vehicle master data cannot be updated directly.');
        }
        $this->validator->validateUpdate($vehicle, $data);
        $storedDocumentPaths = [];

        try {
            return DB::transaction(function () use ($vehicle, $data, &$storedDocumentPaths): Vehicle {
                $attributes = [];
                foreach ([
                    'code' => $data->code,
                    'vehicle_make_id' => $data->vehicleMakeId,
                    'vehicle_model_id' => $data->vehicleModelId,
                    'vehicle_type_id' => $data->vehicleTypeId,
                    'vehicle_category_id' => $data->vehicleCategoryId,
                    'registration_number' => $data->registrationNumber,
                    'chassis_number' => $data->chassisNumber,
                    'engine_number' => $data->engineNumber,
                    'vin_number' => $data->vinNumber,
                    'manufacture_year' => $data->manufactureYear,
                    'registration_date' => $data->registrationDate,
                    'color' => $data->color,
                    'fuel_type' => $data->fuelType,
                    'transmission_type' => $data->transmissionType,
                    'odometer_reading' => $data->odometerReading !== null ? $this->math->normalize($data->odometerReading) : null,
                    'odometer_unit' => $data->odometerUnit,
                    'fuel_level' => $data->fuelLevel,
                    'notes' => $data->notes,
                    'metadata' => $data->metadata,
                ] as $key => $value) {
                    if (in_array($key, $data->provided, true)) {
                        $attributes[$key] = $value;
                    }
                }
                $vehicle->fill($attributes)->save();

                if ($data->documents !== null) {
                    $this->documents->replace($vehicle, $data->documents, static function (string $path) use (&$storedDocumentPaths): void {
                        $storedDocumentPaths[] = $path;
                    });
                }
                if ($data->ownerships !== null) {
                    $this->ownerships->replace($vehicle, $data->ownerships);
                }
                if ($data->attributes !== null) {
                    $this->attributes->replace($vehicle, $data->attributes);
                }

                return $vehicle->refresh()->load(['make', 'model', 'type', 'category', 'documents', 'ownerships', 'currentOwnerships', 'currentCustomerVehicles.customer', 'currentSupplierVehicles.supplier', 'attributes']);
            });
        } catch (Throwable $exception) {
            foreach ($storedDocumentPaths as $path) {
                $this->documents->deleteStoredFile($path, warnOnly: true);
            }

            throw $exception;
        }
    }
}
