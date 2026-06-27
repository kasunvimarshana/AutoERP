<?php

declare(strict_types=1);

namespace Modules\Vehicle\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Vehicle\DTOs\CreateVehicleData;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Validators\VehicleValidationService;
use Throwable;

final class VehicleCreationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly VehicleValidationService $validator,
        private readonly VehicleNumberService $numbers,
        private readonly VehicleDocumentService $documents,
        private readonly VehicleOwnershipService $ownerships,
        private readonly VehicleAttributeService $attributes,
        private readonly VehicleStatusService $statuses,
    ) {}

    public function create(CreateVehicleData $data): Vehicle
    {
        $this->validator->validateCreate($data);
        $storedDocumentPaths = [];

        try {
            return DB::transaction(function () use ($data, &$storedDocumentPaths): Vehicle {
                $vehicle = Vehicle::query()->create([
                    'tenant_id' => $data->tenantId,
                    'organization_unit_id' => $data->organizationUnitId,
                    'vehicle_number' => $data->vehicleNumber ?? $this->numbers->next($data->tenantId),
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
                    'odometer_reading' => $this->math->normalize($data->odometerReading),
                    'odometer_unit' => $data->odometerUnit,
                    'fuel_level' => $data->fuelLevel,
                    'status' => $data->status,
                    'notes' => $data->notes,
                    'metadata' => $data->metadata,
                    'approved_by' => $data->status->value === 'active' ? $data->createdBy : null,
                    'approved_at' => $data->status->value === 'active' ? now() : null,
                ]);

                foreach ($data->documents as $document) {
                    $this->documents->create($vehicle, $document, static function (string $path) use (&$storedDocumentPaths): void {
                        $storedDocumentPaths[] = $path;
                    });
                }
                foreach ($data->ownerships as $ownership) {
                    $this->ownerships->assign($vehicle, $ownership, $data->createdBy);
                }
                foreach ($data->attributes as $attribute) {
                    $this->attributes->create($vehicle, $attribute);
                }
                $this->statuses->recordInitial($vehicle, $data->createdBy);

                return $vehicle->refresh()->load(['make', 'model', 'type', 'category', 'documents', 'ownerships', 'currentOwnerships', 'attributes', 'statusHistories']);
            });
        } catch (Throwable $exception) {
            foreach ($storedDocumentPaths as $path) {
                $this->documents->deleteStoredFile($path, warnOnly: true);
            }

            throw $exception;
        }
    }
}
