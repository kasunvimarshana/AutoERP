<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class RentalOwnerVehicleLookupRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'agreement_id' => ['required', 'integer', $this->tenantExists('vehicle_rental_agreements')],
            'date_from' => RentalDateTimeRules::required(),
            'date_to' => [...RentalDateTimeRules::nullable(), 'after_or_equal:date_from'],
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
