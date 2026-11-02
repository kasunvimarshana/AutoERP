<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\DriverMode;
use Modules\VehicleRental\Enums\RentalBasis;

final class UpdateRentalReservationRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'expected_version' => ['required', 'integer', 'min:1'],
            'customer_id' => ['sometimes', 'required', 'integer'],
            'requested_vehicle_id' => ['nullable', 'integer'],
            'requested_vehicle_category_id' => ['nullable', 'integer'],
            'rental_mode' => ['sometimes', 'required', Rule::enum(DriverMode::class)],
            'billing_cycle' => ['sometimes', 'required', Rule::enum(RentalBasis::class)],
            'requested_start_at' => ['sometimes', 'required', 'date'],
            'requested_end_at' => ['sometimes', 'required', 'date'],
            'currency_id' => ['sometimes', 'required', 'integer'],
            'estimated_amount' => ['nullable', 'numeric', 'min:0'],
            'estimated_deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'source' => ['nullable', 'string', 'max:50'],
            'remarks' => ['nullable', 'string', 'max:1000'],
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
        return (int) $this->validated('expected_version');
    }
}
