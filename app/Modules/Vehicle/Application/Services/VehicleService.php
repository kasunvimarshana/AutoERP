<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;
use Modules\Vehicle\Application\Actions\DeleteVehicleRecordAction;
use Modules\Vehicle\Application\Actions\FindVehicleRecordAction;
use Modules\Vehicle\Application\Actions\ListVehicleRecordsAction;
use Modules\Vehicle\Application\Actions\PersistVehicleRecordAction;
use Modules\Vehicle\Application\DTOs\VehicleData;
use Modules\Vehicle\Application\DTOs\VehicleDocumentData;
use Modules\Vehicle\Application\Repositories\VehicleDocumentRepositoryInterface;
use Modules\Vehicle\Application\Repositories\VehicleRepositoryInterface;
use Modules\Vehicle\Domain\Exceptions\VehicleRecordNotFoundException;
use Modules\Vehicle\Domain\Services\VehicleDomainService;

class VehicleService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly VehicleRepositoryInterface $vehicles,
        private readonly VehicleDocumentRepositoryInterface $documents,
        private readonly VehicleDomainService $domain,
        private readonly ListVehicleRecordsAction $listRecords,
        private readonly FindVehicleRecordAction $findRecord,
        private readonly PersistVehicleRecordAction $persistRecord,
        private readonly DeleteVehicleRecordAction $deleteRecord,
    ) {
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listVehicles(
        int|string $tenantId,
        array $filters = [],
        ?int $perPage = null,
    ): Collection|LengthAwarePaginator {
        $this->findTenant($tenantId);

        return $this->listRecords->execute(
            $this->vehicles,
            array_merge(['tenant_id' => (int) $tenantId], $filters),
            $perPage,
        );
    }

    public function findVehicle(int|string $tenantId, int|string $id): Model
    {
        $record = $this->vehicles->findForTenantById($tenantId, $id);

        if ($record === null) {
            throw VehicleRecordNotFoundException::for('Vehicle', $id);
        }

        return $record;
    }

    public function createVehicle(VehicleData $data): Model
    {
        $this->findTenant($data->tenantId);

        return $this->persistRecord->create($this->vehicles, $this->vehicleAttributes($data));
    }

    public function updateVehicle(int|string $tenantId, int|string $id, VehicleData $data): Model
    {
        return $this->persistRecord->update(
            $this->vehicles,
            $this->findVehicle($tenantId, $id),
            $this->vehicleAttributes($data),
        );
    }

    public function deleteVehicle(int|string $tenantId, int|string $id): bool
    {
        return $this->deleteRecord->execute($this->vehicles, $this->findVehicle($tenantId, $id));
    }

    public function listDocuments(
        int|string $tenantId,
        int|string $vehicleId,
        ?int $perPage = null,
    ): Collection|LengthAwarePaginator {
        $this->findVehicle($tenantId, $vehicleId);

        return $perPage === null
            ? $this->documents->getForVehicle($vehicleId)
            : $this->documents->paginateForVehicle($vehicleId, $perPage);
    }

    public function findDocument(int|string $tenantId, int|string $vehicleId, int|string $id): Model
    {
        $record = $this->documents->findForTenantAndVehicleById($tenantId, $vehicleId, $id);

        if ($record === null) {
            throw VehicleRecordNotFoundException::for('Vehicle document', $id);
        }

        return $record;
    }

    public function createDocument(VehicleDocumentData $data): Model
    {
        $vehicle = $this->findVehicle($data->tenantId, $data->vehicleId);

        return $this->persistRecord->create($this->documents, $this->documentAttributes($data, $vehicle));
    }

    public function updateDocument(
        int|string $tenantId,
        int|string $vehicleId,
        int|string $id,
        VehicleDocumentData $data,
    ): Model {
        $vehicle = $this->findVehicle($tenantId, $vehicleId);

        return $this->persistRecord->update(
            $this->documents,
            $this->findDocument($tenantId, $vehicleId, $id),
            $this->documentAttributes($data, $vehicle),
        );
    }

    public function deleteDocument(int|string $tenantId, int|string $vehicleId, int|string $id): bool
    {
        return $this->deleteRecord->execute($this->documents, $this->findDocument($tenantId, $vehicleId, $id));
    }

    private function findTenant(int|string $tenantId): Model
    {
        return $this->findRecord->execute($this->tenants, 'Tenant', $tenantId);
    }

    /**
     * @return array<string, mixed>
     */
    private function vehicleAttributes(VehicleData $data): array
    {
        return [
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'vehicle_code' => $this->domain->normalizeVehicleCode($data->vehicleCode),
            'vin' => $this->domain->normalizeVin($data->vin),
            'license_plate' => $this->domain->normalizeText($data->licensePlate),
            'make' => $this->domain->normalizeText($data->make),
            'model' => $this->domain->normalizeText($data->model),
            'year' => $this->domain->normalizeYear($data->year),
            'color' => $this->domain->normalizeText($data->color),
            'category' => $this->domain->normalizeText($data->category),
            'usage_profile' => $this->domain->normalizeUsageProfile($data->usageProfile),
            'fuel_type' => $this->domain->normalizeText($data->fuelType),
            'transmission' => $this->domain->normalizeText($data->transmission),
            'seating_capacity' => $this->domain->normalizeNullablePositiveInt(
                $data->seatingCapacity,
                'Seating capacity',
            ),
            'current_odometer' => $this->domain->normalizePositiveInt($data->currentOdometer, 'Current odometer'),
            'status' => $this->domain->normalizeStatus($data->status),
            'registration_expiry' => $data->registrationExpiry,
            'insurance_expiry' => $data->insuranceExpiry,
            'last_service_date' => $data->lastServiceDate,
            'last_service_odometer' => $this->domain->normalizeNullablePositiveInt(
                $data->lastServiceOdometer,
                'Last service odometer',
            ),
            'next_service_due_date' => $data->nextServiceDueDate,
            'next_service_due_odometer' => $this->domain->normalizeNullablePositiveInt(
                $data->nextServiceDueOdometer,
                'Next service due odometer',
            ),
            'metadata' => $this->domain->normalizeMetadata($data->metadata),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function documentAttributes(VehicleDocumentData $data, Model $vehicle): array
    {
        return [
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId ?? $vehicle->organization_unit_id,
            'vehicle_id' => $data->vehicleId,
            'name' => (string) $this->domain->normalizeText($data->name),
            'file_path' => (string) $this->domain->normalizeText($data->filePath),
            'mime_type' => $this->domain->normalizeText($data->mimeType),
            'size' => $this->domain->normalizeNullablePositiveInt($data->size, 'Size'),
            'type' => $this->domain->normalizeText($data->type),
            'metadata' => $this->domain->normalizeMetadata($data->metadata),
        ];
    }
}
