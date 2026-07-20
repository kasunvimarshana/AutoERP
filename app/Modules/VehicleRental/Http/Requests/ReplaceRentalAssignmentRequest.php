<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\DTOs\RentalReplacementData;

final class ReplaceRentalAssignmentRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'integer', $this->tenantExists('vehicles')],
            'effective_at' => RentalDateTimeRules::required(),
            'old_return_odometer' => ['required', 'numeric', 'min:0'],
            'new_handover_odometer' => ['required', 'numeric', 'min:0'],
            'source_assignment_id' => ['nullable', 'integer', $this->tenantExists('vehicle_rental_assignments')],
            'driver_employee_id' => ['nullable', 'integer', $this->tenantExists('hr_employees')],
            'self_drive' => ['nullable', 'boolean'],
            'reason' => ['required', 'string', 'max:255'],
            'old_fuel_level' => ['nullable', 'string', 'max:50'],
            'new_fuel_level' => ['nullable', 'string', 'max:50'],
            'old_condition_notes' => ['nullable', 'string'],
            'new_condition_notes' => ['nullable', 'string'],
            'old_damage_notes' => ['nullable', 'string'],
            'new_damage_notes' => ['nullable', 'string'],
            'expected_version' => ['required', 'integer', 'min:1'],
        ];
    }

    public function toData(): RentalReplacementData
    {
        return new RentalReplacementData(
            tenantId: $this->tenantId(),
            organizationUnitId: $this->organizationUnitId(),
            vehicleId: (int) $this->validated('vehicle_id'),
            effectiveAt: (string) $this->validated('effective_at'),
            oldReturnOdometer: (string) $this->validated('old_return_odometer'),
            newHandoverOdometer: (string) $this->validated('new_handover_odometer'),
            sourceAssignmentId: $this->integerOrNull('source_assignment_id'),
            driverEmployeeId: $this->integerOrNull('driver_employee_id'),
            selfDrive: (bool) ($this->validated('self_drive') ?? false),
            reason: trim((string) $this->validated('reason')),
            oldFuelLevel: $this->nullableString('old_fuel_level'),
            newFuelLevel: $this->nullableString('new_fuel_level'),
            oldConditionNotes: $this->nullableString('old_condition_notes'),
            newConditionNotes: $this->nullableString('new_condition_notes'),
            oldDamageNotes: $this->nullableString('old_damage_notes'),
            newDamageNotes: $this->nullableString('new_damage_notes'),
            actorId: $this->currentUserId(),
        );
    }

    public function expectedVersion(): int
    {
        return (int) $this->validated('expected_version');
    }

    private function integerOrNull(string $key): ?int
    {
        $value = $this->validated($key);

        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    private function nullableString(string $key): ?string
    {
        $value = $this->validated($key);

        return is_scalar($value) && trim((string) $value) !== '' ? trim((string) $value) : null;
    }
}
