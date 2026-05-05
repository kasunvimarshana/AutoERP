<?php

declare(strict_types=1);

namespace Modules\Fleet\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_type_id'                  => ['sometimes', 'integer', 'exists:fleet_vehicle_types,id'],
            'color'                            => ['sometimes', 'nullable', 'string', 'max:30'],
            'is_rentable'                      => ['sometimes', 'boolean'],
            'is_serviceable'                   => ['sometimes', 'boolean'],
            'owner_supplier_id'                => ['sometimes', 'nullable', 'integer'],
            'owner_commission_pct'             => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'asset_account_id'                 => ['sometimes', 'nullable', 'integer'],
            'accum_depreciation_account_id'    => ['sometimes', 'nullable', 'integer'],
            'depreciation_expense_account_id'  => ['sometimes', 'nullable', 'integer'],
            'rental_revenue_account_id'        => ['sometimes', 'nullable', 'integer'],
            'service_revenue_account_id'       => ['sometimes', 'nullable', 'integer'],
            'acquisition_cost'                 => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'metadata'                         => ['sometimes', 'nullable', 'array'],
            'is_active'                        => ['sometimes', 'boolean'],
        ];
    }
}
