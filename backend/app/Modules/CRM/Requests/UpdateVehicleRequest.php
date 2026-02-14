<?php

namespace App\Modules\CRM\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Vehicle Request
 * 
 * Validation rules for updating an existing vehicle
 * 
 * @package App\Modules\CRM\Requests
 */
class UpdateVehicleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $vehicleId = $this->route('id') ?? $this->route('vehicle');
        
        return [
            'make' => 'sometimes|required|string|max:100',
            'model' => 'sometimes|required|string|max:100',
            'year' => 'sometimes|required|integer|min:1900|max:' . (date('Y') + 1),
            'vin' => 'nullable|string|max:17|unique:vehicles,vin,' . $vehicleId,
            'license_plate' => 'nullable|string|max:20',
            'color' => 'nullable|string|max:50',
            'mileage' => 'nullable|integer|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'vin.unique' => 'This VIN is already registered to another vehicle.',
        ];
    }
}
