<?php

namespace Modules\AssetManagement\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_category_id'   => ['nullable', 'string'],
            'name'                => ['required', 'string', 'max:255'],
            'description'         => ['nullable', 'string'],
            'serial_number'       => ['nullable', 'string', 'max:255'],
            'location'            => ['nullable', 'string', 'max:255'],
            'purchase_date'       => ['nullable', 'date'],
            'purchase_cost'       => ['nullable', 'numeric', 'min:0'],
            'salvage_value'       => ['nullable', 'numeric', 'min:0'],
            'useful_life_years'   => ['nullable', 'integer', 'min:0'],
            'depreciation_method' => ['nullable', 'string', 'in:straight_line,declining_balance'],
        ];
    }
}
