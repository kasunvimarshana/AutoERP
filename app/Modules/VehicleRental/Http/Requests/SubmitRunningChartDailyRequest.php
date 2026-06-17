<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\DTOs\RentalUsageEventData;
use Modules\VehicleRental\DTOs\RentalUsageLogData;
use Modules\VehicleRental\Enums\RentalUsageEventType;
use Modules\VehicleRental\Services\RentalUsageContextService;

final class SubmitRunningChartDailyRequest extends TenantScopedRequest
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
            'usage_date' => ['required', 'date'],
            'trips' => ['required', 'array', 'min:1'],
            'trips.*.id' => ['nullable', 'integer', 'min:1'],
            'trips.*.driver_id' => ['nullable', 'integer', 'min:1'],
            'trips.*.start_time' => ['required', 'date_format:H:i'],
            'trips.*.end_time' => ['required', 'date_format:H:i'],
            'trips.*.start_odometer' => ['required', 'decimal:0,6', 'min:0'],
            'trips.*.end_odometer' => ['required', 'decimal:0,6', 'gte:trips.*.start_odometer'],
            'trips.*.comparative_km' => ['nullable', 'decimal:0,6', 'min:0'],
            'trips.*.trip_from' => ['nullable', 'string', 'max:255'],
            'trips.*.trip_to' => ['nullable', 'string', 'max:255'],
            'trips.*.trip_purpose' => ['nullable', 'string', 'max:255'],
            'trips.*.odometer_variance_reason' => ['nullable', 'string', 'max:1000'],
            'trips.*.remarks' => ['nullable', 'string'],
            'trips.*.events' => ['array'],
            'trips.*.events.*.event_type' => ['required', Rule::enum(RentalUsageEventType::class)],
            'trips.*.events.*.quantity' => ['required', 'decimal:0,6', 'gt:0'],
            'trips.*.events.*.remarks' => ['nullable', 'string'],
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

    /**
     * @return list<array<string, mixed>>
     */
    public function tripRows(): array
    {
        return $this->input('trips', []);
    }

    /**
     * @param  array<string, mixed>  $trip
     */
    public function tripId(array $trip): ?int
    {
        return isset($trip['id']) && is_numeric($trip['id']) ? (int) $trip['id'] : null;
    }

    /**
     * @param  array<string, mixed>  $trip
     */
    public function toData(array $trip): RentalUsageLogData
    {
        return new RentalUsageLogData(
            agreementVehicleId: $this->selectedAgreementVehicleId(),
            usageDate: (string) $this->input('usage_date'),
            startOdometer: (string) $trip['start_odometer'],
            endOdometer: (string) $trip['end_odometer'],
            driverId: isset($trip['driver_id']) && is_numeric($trip['driver_id']) ? (int) $trip['driver_id'] : null,
            startTime: (string) $trip['start_time'],
            endTime: (string) $trip['end_time'],
            comparativeKm: isset($trip['comparative_km']) && trim((string) $trip['comparative_km']) !== ''
                ? (string) $trip['comparative_km']
                : null,
            tripFrom: isset($trip['trip_from']) && trim((string) $trip['trip_from']) !== ''
                ? (string) $trip['trip_from']
                : null,
            tripTo: isset($trip['trip_to']) && trim((string) $trip['trip_to']) !== ''
                ? (string) $trip['trip_to']
                : null,
            tripPurpose: isset($trip['trip_purpose']) && trim((string) $trip['trip_purpose']) !== ''
                ? (string) $trip['trip_purpose']
                : null,
            odometerVarianceReason: isset($trip['odometer_variance_reason']) && trim((string) $trip['odometer_variance_reason']) !== ''
                ? (string) $trip['odometer_variance_reason']
                : null,
            remarks: isset($trip['remarks']) && trim((string) $trip['remarks']) !== ''
                ? (string) $trip['remarks']
                : null,
            createdBy: $this->currentUserId(),
        );
    }

    /**
     * @param  array<string, mixed>  $trip
     * @return list<RentalUsageEventData>
     */
    public function eventData(array $trip): array
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
            $trip['events'] ?? [],
        );
    }
}
