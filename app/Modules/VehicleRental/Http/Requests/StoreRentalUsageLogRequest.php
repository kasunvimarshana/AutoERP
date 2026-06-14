<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\DTOs\RentalUsageLogData;

final class StoreRentalUsageLogRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'agreement_vehicle_id' => ['required', 'integer', 'min:1'],
            'driver_id' => ['nullable', 'integer', 'min:1'],
            'usage_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i', 'required_with:end_time'],
            'end_time' => ['nullable', 'date_format:H:i', 'required_with:start_time'],
            'start_odometer' => ['required', 'decimal:0,6', 'min:0'],
            'end_odometer' => ['required', 'decimal:0,6', 'gte:start_odometer'],
            'comparative_km' => ['nullable', 'decimal:0,6', 'min:0'],
            'trip_from' => ['nullable', 'string', 'max:255'],
            'trip_to' => ['nullable', 'string', 'max:255'],
            'trip_purpose' => ['nullable', 'string', 'max:255'],
            'odometer_variance_reason' => ['nullable', 'string', 'max:1000'],
            'remarks' => ['nullable', 'string'],
        ];
    }

    public function toData(): RentalUsageLogData
    {
        return new RentalUsageLogData(
            agreementVehicleId: (int) $this->input('agreement_vehicle_id'),
            usageDate: (string) $this->input('usage_date'),
            startOdometer: (string) $this->input('start_odometer'),
            endOdometer: (string) $this->input('end_odometer'),
            driverId: $this->filled('driver_id') ? (int) $this->input('driver_id') : null,
            startTime: $this->filled('start_time') ? (string) $this->input('start_time') : null,
            endTime: $this->filled('end_time') ? (string) $this->input('end_time') : null,
            comparativeKm: $this->filled('comparative_km') ? (string) $this->input('comparative_km') : null,
            tripFrom: $this->filled('trip_from') ? (string) $this->input('trip_from') : null,
            tripTo: $this->filled('trip_to') ? (string) $this->input('trip_to') : null,
            tripPurpose: $this->filled('trip_purpose') ? (string) $this->input('trip_purpose') : null,
            odometerVarianceReason: $this->filled('odometer_variance_reason')
                ? (string) $this->input('odometer_variance_reason')
                : null,
            remarks: $this->filled('remarks') ? (string) $this->input('remarks') : null,
            createdBy: $this->currentUserId(),
        );
    }
}
