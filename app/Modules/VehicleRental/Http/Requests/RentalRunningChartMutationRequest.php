<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\DTOs\RentalRunningChartData;
use Modules\VehicleRental\Enums\RentalAcMode;

abstract class RentalRunningChartMutationRequest extends TenantScopedRequest
{
    /** @return array<string, list<mixed>> */
    protected function runningChartRules(bool $includeAssignment): array
    {
        $rules = [
            'operational_date' => ['required', 'date'],
            'starts_at' => RentalDateTimeRules::required(),
            'ends_at' => [...RentalDateTimeRules::required(), 'after:starts_at'],
            'start_odometer' => ['required', 'numeric', 'min:0'],
            'end_odometer' => ['required', 'numeric', 'min:0'],
            'garage_km' => ['nullable', 'numeric', 'min:0'],
            'normal_overtime_hours' => ['nullable', 'numeric', 'min:0'],
            'double_overtime_hours' => ['nullable', 'numeric', 'min:0'],
            'triple_overtime_hours' => ['nullable', 'numeric', 'min:0'],
            'night_out_count' => ['nullable', 'integer', 'min:0'],
            'ac_mode' => ['nullable', Rule::enum(RentalAcMode::class)],
            'trip_origin' => ['nullable', 'string', 'max:255'],
            'trip_destination' => ['nullable', 'string', 'max:255'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'odometer_variance_reason' => ['nullable', 'string', 'max:500'],
            'remarks' => ['nullable', 'string', 'max:5000'],
        ];
        if ($includeAssignment) {
            $rules = [
                'assignment_id' => ['required', 'integer', $this->tenantExists('vehicle_rental_assignments')],
                ...$rules,
            ];
        }

        return $rules;
    }

    public function toData(?int $assignmentId = null): RentalRunningChartData
    {
        $acMode = $this->validated('ac_mode');

        return new RentalRunningChartData(
            tenantId: $this->tenantId(),
            organizationUnitId: $this->organizationUnitId(),
            assignmentId: $assignmentId ?? (int) $this->validated('assignment_id'),
            operationalDate: (string) $this->validated('operational_date'),
            startsAt: (string) $this->validated('starts_at'),
            endsAt: (string) $this->validated('ends_at'),
            startOdometer: (string) $this->validated('start_odometer'),
            endOdometer: (string) $this->validated('end_odometer'),
            garageKm: (string) ($this->validated('garage_km') ?? '0'),
            normalOvertimeHours: (string) ($this->validated('normal_overtime_hours') ?? '0'),
            doubleOvertimeHours: (string) ($this->validated('double_overtime_hours') ?? '0'),
            tripleOvertimeHours: (string) ($this->validated('triple_overtime_hours') ?? '0'),
            nightOutCount: (int) ($this->validated('night_out_count') ?? 0),
            acMode: is_string($acMode) && $acMode !== '' ? RentalAcMode::from($acMode) : null,
            tripOrigin: $this->nullableString('trip_origin'),
            tripDestination: $this->nullableString('trip_destination'),
            purpose: $this->nullableString('purpose'),
            odometerVarianceReason: $this->nullableString('odometer_variance_reason'),
            remarks: $this->nullableString('remarks'),
            actorId: $this->currentUserId(),
        );
    }

    private function nullableString(string $key): ?string
    {
        $value = $this->validated($key);

        return is_scalar($value) && trim((string) $value) !== '' ? trim((string) $value) : null;
    }
}
