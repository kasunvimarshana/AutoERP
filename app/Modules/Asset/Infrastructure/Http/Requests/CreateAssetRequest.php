<?php declare(strict_types=1);

namespace Modules\Asset\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
            'serial_number' => 'nullable|string|max:255|unique:asset_models,serial_number',
            'asset_owner_id' => 'required|string|uuid',
            'purchase_date' => 'required|date',
            'acquisition_cost' => 'required|numeric|decimal:0,6',
            'status' => 'nullable|string|in:active,maintenance,retired,sold,damaged',
            'depreciation_method' => 'nullable|string|in:straight_line,declining_balance,units_of_production',
            'useful_life_years' => 'required|integer|min:1',
            'salvage_value' => 'required|numeric|decimal:0,6',
        ];
    }
}
