<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\ValidationException;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\Data\AgreementContext;

final class VehicleDriverReassignmentRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'expected_version' => ['sometimes', 'required', 'integer', 'min:1'],
            'driver_employee_id' => ['sometimes', 'required', 'integer', 'min:1'],
            'new_vehicle_id' => ['sometimes', 'required', 'integer', 'min:1'],
            'new_owner_agreement_id' => ['nullable', 'integer'],
            'new_handover_odometer' => ['nullable', 'numeric'],
            'return_odometer' => ['nullable', 'numeric'],
            'cutover_at' => ['sometimes', 'required', 'date'],
            'reason' => ['required', 'string', 'max:1000'],
            'customer_agreement_id' => ['nullable', 'integer'],
        ];
    }

    public function context(): AgreementContext
    {
        if ($this->organizationUnitId() === null || $this->currentUserId() === null) {
            throw ValidationException::withMessages(['context' => ['Select an organization and sign in before using Rental.']]);
        }

        return new AgreementContext($this->tenantId(), $this->organizationUnitId(), $this->currentUserId());
    }

    public function expectedVersion(): int
    {
        if (! $this->filled('expected_version')) {
            throw ValidationException::withMessages(['expected_version' => ['Reload the record and supply its current version.']]);
        }

        return (int) $this->input('expected_version');
    }
}
