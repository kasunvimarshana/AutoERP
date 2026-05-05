<?php

declare(strict_types=1);

namespace Modules\Fleet\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_type_id'                  => ['required', 'integer', 'exists:fleet_vehicle_types,id'],
            'registration_number'              => ['required', 'string', 'max:30'],
            'make'                             => ['required', 'string', 'max:60'],
            'model'                            => ['required', 'string', 'max:60'],
            'year'                             => ['required', 'integer', 'min:1900', 'max:2100'],
            'ownership_type'                   => ['required', 'in:owned,third_party'],
            'is_rentable'                      => ['required', 'boolean'],
            'is_serviceable'                   => ['required', 'boolean'],
            'fuel_type'                        => ['required', 'in:petrol,diesel,electric,hybrid,lpg,cng'],
            'transmission'                     => ['required', 'in:manual,automatic,cvt'],
            'seating_capacity'                 => ['required', 'integer', 'min:1'],
            'color'                            => ['nullable', 'string', 'max:30'],
            'vin_number'                       => ['nullable', 'string', 'max:50'],
            'engine_number'                    => ['nullable', 'string', 'max:50'],
            'owner_supplier_id'                => ['nullable', 'integer'],
            'owner_commission_pct'             => ['nullable', 'numeric', 'min:0', 'max:100'],
            'fuel_capacity'                    => ['nullable', 'numeric', 'min:0'],
            'asset_account_id'                 => ['nullable', 'integer'],
            'accum_depreciation_account_id'    => ['nullable', 'integer'],
            'depreciation_expense_account_id'  => ['nullable', 'integer'],
            'rental_revenue_account_id'        => ['nullable', 'integer'],
            'service_revenue_account_id'       => ['nullable', 'integer'],
            'acquisition_cost'                 => ['nullable', 'numeric', 'min:0'],
            'acquired_at'                      => ['nullable', 'date'],
            'org_unit_id'                      => ['nullable', 'integer'],
            'metadata'                         => ['nullable', 'array'],
        ];
    }
}
