<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleService\DTOs\VehicleServiceInspectionData;
use Modules\VehicleService\Http\Requests\Concerns\NormalizesBooleanInput;

final class StoreVehicleServiceInspectionRequest extends TenantScopedRequest
{
    use NormalizesBooleanInput;

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'customer_complaint' => ['nullable', 'string'],
            'inspection_notes' => ['nullable', 'string'],
            'diagnosis' => ['nullable', 'string'],
            'recommended_work' => ['nullable', 'string'],
            'odometer_reading' => ['nullable', 'decimal:0,6', 'min:0'],
            'fuel_level' => ['nullable', 'string', 'max:100'],
            'mark_inspected' => ['nullable', 'boolean'],
        ];
    }

    public function toData(bool $forceInspected = false): VehicleServiceInspectionData
    {
        return new VehicleServiceInspectionData(
            customerComplaint: $this->stringOrNull('customer_complaint'),
            inspectionNotes: $this->stringOrNull('inspection_notes'),
            diagnosis: $this->stringOrNull('diagnosis'),
            recommendedWork: $this->stringOrNull('recommended_work'),
            odometerReading: $this->stringOrNull('odometer_reading'),
            fuelLevel: $this->stringOrNull('fuel_level'),
            inspectedBy: $this->currentUserId(),
            markInspected: $forceInspected || $this->boolean('mark_inspected'),
        );
    }

    private function stringOrNull(string $key): ?string
    {
        return $this->filled($key) ? (string) $this->input($key) : null;
    }
}
