<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

final class UpdateRentalReservationRequest extends StoreRentalReservationRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['expected_version'] = ['required', 'integer', 'min:1'];

        return $rules;
    }
}
