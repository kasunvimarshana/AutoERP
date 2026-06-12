<?php

declare(strict_types=1);

namespace Modules\Vehicle\Services;

use InvalidArgumentException;
use Modules\Vehicle\DTOs\VehicleDocumentData;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Models\VehicleDocument;

final class VehicleDocumentService
{
    public function create(Vehicle $vehicle, VehicleDocumentData $data): VehicleDocument
    {
        return $vehicle->documents()->create($this->attributes($vehicle, $data));
    }

    public function update(Vehicle $vehicle, VehicleDocument $document, VehicleDocumentData $data): VehicleDocument
    {
        $this->assertOwned($vehicle, $document);
        $document->fill($this->attributes($vehicle, $data, false))->save();
        return $document->refresh();
    }

    public function delete(Vehicle $vehicle, VehicleDocument $document): void
    {
        $this->assertOwned($vehicle, $document);
        $document->delete();
    }

    /** @param list<VehicleDocumentData> $documents */
    public function replace(Vehicle $vehicle, array $documents): void
    {
        $vehicle->documents()->delete();
        foreach ($documents as $document) { $this->create($vehicle, $document); }
    }

    public function expiring(int $tenantId, ?int $organizationUnitId, int $days = 30)
    {
        return VehicleDocument::query()
            ->where('tenant_id', $tenantId)
            ->when($organizationUnitId !== null, fn ($query) => $query->where(fn ($scope) => $scope->whereNull('organization_unit_id')->orWhere('organization_unit_id', $organizationUnitId)))
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays($days)->toDateString())
            ->orderBy('expiry_date')
            ->get();
    }

    private function attributes(Vehicle $vehicle, VehicleDocumentData $data, bool $includeScope = true): array
    {
        return [
            ...($includeScope ? ['tenant_id' => $vehicle->tenant_id, 'organization_unit_id' => $vehicle->organization_unit_id] : []),
            'document_type' => $data->documentType,
            'document_number' => $data->documentNumber,
            'issued_date' => $data->issuedDate,
            'expiry_date' => $data->expiryDate,
            'file_path' => $data->filePath,
            'status' => $data->status,
            'notes' => $data->notes,
        ];
    }

    private function assertOwned(Vehicle $vehicle, VehicleDocument $document): void
    {
        if ((int) $document->vehicle_id !== (int) $vehicle->getKey()) {
            throw new InvalidArgumentException('Vehicle document does not belong to the vehicle.');
        }
    }
}
