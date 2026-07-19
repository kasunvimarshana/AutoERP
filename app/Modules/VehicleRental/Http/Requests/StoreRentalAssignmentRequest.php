<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\DTOs\RentalAssignmentData;
use Modules\VehicleRental\Enums\RentalAssignmentSide;

final class StoreRentalAssignmentRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'agreement_id' => ['required', 'integer', $this->tenantExists('vehicle_rental_agreements')],
            'vehicle_id' => ['required', 'integer', $this->tenantExists('vehicles')],
            'side' => ['required', Rule::enum(RentalAssignmentSide::class)],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'source_assignment_id' => ['nullable', 'integer', $this->tenantExists('vehicle_rental_assignments')],
            'handover_odometer' => ['nullable', 'numeric', 'min:0'],
            'driver_employee_id' => ['nullable', 'integer', $this->tenantExists('hr_employees')],
            'self_drive' => ['nullable', 'boolean'],
        ];
    }

    public function toData(): RentalAssignmentData
    {
        return new RentalAssignmentData(
            tenantId: $this->tenantId(),
            organizationUnitId: $this->organizationUnitId(),
            agreementId: (int) $this->validated('agreement_id'),
            vehicleId: (int) $this->validated('vehicle_id'),
            side: RentalAssignmentSide::from((string) $this->validated('side')),
            startsAt: (string) $this->validated('starts_at'),
            endsAt: $this->nullableString('ends_at'),
            sourceAssignmentId: $this->integerOrNull('source_assignment_id'),
            handoverOdometer: $this->nullableString('handover_odometer'),
            driverEmployeeId: $this->integerOrNull('driver_employee_id'),
            selfDrive: (bool) ($this->validated('self_drive') ?? false),
            actorId: $this->currentUserId(),
        );
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
