<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\DTOs\RentalUsageEventData;
use Modules\VehicleRental\DTOs\RentalUsageLogData;
use Modules\VehicleRental\Enums\RentalUsageEventType;
use Modules\VehicleRental\Services\RentalUsageContextService;

final class StoreRunningChartTripRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'mode' => ['required', Rule::in([
                RentalUsageContextService::MODE_LESSEE,
                RentalUsageContextService::MODE_LESSOR,
                RentalUsageContextService::MODE_LINKED,
            ])],
            'lessee_agreement_id' => ['required_if:mode,lessee,linked', 'integer', 'min:1'],
            'lessee_agreement_vehicle_id' => ['required_if:mode,lessee,linked', 'integer', 'min:1'],
            'lessor_agreement_id' => ['required_if:mode,lessor,linked', 'integer', 'min:1'],
            'lessor_agreement_vehicle_id' => ['required_if:mode,lessor,linked', 'integer', 'min:1'],
            'driver_id' => ['nullable', 'integer', 'min:1'],
            'usage_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'start_odometer' => ['required', 'decimal:0,6', 'min:0'],
            'end_odometer' => ['required', 'decimal:0,6', 'gte:start_odometer'],
            'comparative_km' => ['nullable', 'decimal:0,6', 'min:0'],
            'trip_from' => ['nullable', 'string', 'max:255'],
            'trip_to' => ['nullable', 'string', 'max:255'],
            'trip_purpose' => ['nullable', 'string', 'max:255'],
            'odometer_variance_reason' => ['nullable', 'string', 'max:1000'],
            'remarks' => ['nullable', 'string'],
            'events' => ['array'],
            'events.*.event_type' => ['required', Rule::enum(RentalUsageEventType::class)],
            'events.*.quantity' => ['required', 'decimal:0,6', 'gt:0'],
            'events.*.remarks' => ['nullable', 'string'],
        ];
    }

    public function mode(): string
    {
        return (string) $this->input('mode');
    }

    public function selectedAgreementId(): int
    {
        return $this->mode() === RentalUsageContextService::MODE_LESSOR
            ? (int) $this->input('lessor_agreement_id')
            : (int) $this->input('lessee_agreement_id');
    }

    public function selectedAgreementVehicleId(): int
    {
        return $this->mode() === RentalUsageContextService::MODE_LESSOR
            ? (int) $this->input('lessor_agreement_vehicle_id')
            : (int) $this->input('lessee_agreement_vehicle_id');
    }

    public function counterpartAgreementId(): ?int
    {
        return $this->mode() === RentalUsageContextService::MODE_LINKED
            ? (int) $this->input('lessor_agreement_id')
            : null;
    }

    public function counterpartAgreementVehicleId(): ?int
    {
        return $this->mode() === RentalUsageContextService::MODE_LINKED
            ? (int) $this->input('lessor_agreement_vehicle_id')
            : null;
    }

    public function toData(): RentalUsageLogData
    {
        return new RentalUsageLogData(
            agreementVehicleId: $this->selectedAgreementVehicleId(),
            usageDate: (string) $this->input('usage_date'),
            startOdometer: (string) $this->input('start_odometer'),
            endOdometer: (string) $this->input('end_odometer'),
            driverId: $this->filled('driver_id') ? (int) $this->input('driver_id') : null,
            startTime: (string) $this->input('start_time'),
            endTime: (string) $this->input('end_time'),
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

    /**
     * @return list<RentalUsageEventData>
     */
    public function eventData(): array
    {
        return array_map(
            fn (array $event): RentalUsageEventData => new RentalUsageEventData(
                eventType: RentalUsageEventType::from((string) $event['event_type']),
                quantity: (string) $event['quantity'],
                remarks: isset($event['remarks']) && trim((string) $event['remarks']) !== ''
                    ? (string) $event['remarks']
                    : null,
                createdBy: $this->currentUserId(),
            ),
            $this->input('events', []),
        );
    }
}
