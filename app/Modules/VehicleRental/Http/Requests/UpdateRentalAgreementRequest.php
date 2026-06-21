<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

final class UpdateRentalAgreementRequest extends StoreRentalAgreementRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->merge(array_filter([
            'agreement_kind' => $this->input('agreement_kind'),
        ], static fn ($value) => $value !== null));
    }

    public function rules(): array
    {
        $rules = parent::rules();
        foreach ($rules as $key => $rule) {
            if (! in_array($key, ['tenant_id', 'organization_unit_id'], true)) {
                $rules[$key] = array_values(array_filter((array) $rule, static fn ($item) => $item !== 'required'));
                array_unshift($rules[$key], 'sometimes');
            }
        }

        return $rules;
    }
}
