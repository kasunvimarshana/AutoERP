<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\ValidationException;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\Data\AgreementContext;

final class RentalReservationQueryRequest extends TenantScopedRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $merges = [];
        foreach (['status', 'customer_id', 'requested_vehicle_id', 'requested_vehicle_category_id', 'search', 'date_from', 'date_to', 'per_page', 'page'] as $field) {
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
            'status' => ['nullable', 'string', 'max:50'],
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'requested_vehicle_id' => ['nullable', 'integer', 'min:1'],
            'requested_vehicle_category_id' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
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
