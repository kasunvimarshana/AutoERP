<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\DriverMode;
use Modules\VehicleRental\Enums\RentalBasis;

final class StoreRentalReservationRequest extends TenantScopedRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $merges = [];
        foreach (['requested_vehicle_id', 'requested_vehicle_category_id', 'estimated_amount', 'estimated_deposit_amount', 'source', 'remarks'] as $field) {
            if ($this->has($field) && is_string($this->input($field)) && trim($this->input($field)) === '') {
                $merges[$field] = null;
            }
        }

        if ($merges !== []) {
            $this->merge($merges);
        }
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer'],
            'requested_vehicle_id' => ['nullable', 'integer'],
            'requested_vehicle_category_id' => ['nullable', 'integer'],
            'rental_mode' => ['required', Rule::enum(DriverMode::class)],
            'billing_cycle' => ['required', Rule::enum(RentalBasis::class)],
            'requested_start_at' => ['required', 'date'],
            'requested_end_at' => ['required', 'date', 'after:requested_start_at'],
            'currency_id' => ['required', 'integer'],
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
}
