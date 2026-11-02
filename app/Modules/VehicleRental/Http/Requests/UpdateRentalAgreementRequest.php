<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

final class UpdateRentalAgreementRequest extends StoreRentalAgreementRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        foreach ($rules as $key => $rule) {
            if (! in_array($key, ['tenant_id', 'organization_unit_id'], true)) {
                $rules[$key] = array_values(array_filter(
                    (array) $rule,
                    static fn ($item): bool => $item !== 'required',
                ));
                array_unshift($rules[$key], 'sometimes');
            }
        }

        $rules['expected_version'] = ['required', 'integer', 'min:1'];
        foreach ([
            'agreement_number',
            'agreement_kind',
            'reservation_id',
            'expected_reservation_version',
            'activate_rate_version',
            'rate_version',
            'deposit',
        ] as $immutableOrSeparateAggregate) {
            $rules[$immutableOrSeparateAggregate] = ['prohibited'];
        }

        return $rules;
    }
}
