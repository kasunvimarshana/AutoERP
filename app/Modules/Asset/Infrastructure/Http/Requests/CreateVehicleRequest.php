<?php declare(strict_types=1);

namespace Modules\Asset\Infrastructure\Http\Requests;

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
            'tenant_id' => 'required|integer',
            'asset_id' => 'required|string|uuid',
            'registration_number' => 'required|string|max:20|unique:vehicle_models,registration_number',
            'make' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'vin' => 'nullable|string|max:17|unique:vehicle_models,vin',
            'fuel_type' => 'nullable|string|in:gasoline,diesel,electric,hybrid',
            'current_mileage' => 'required|integer|min:0',
            'status' => 'nullable|string|in:active,maintenance,retired',
        ];
    }
}
