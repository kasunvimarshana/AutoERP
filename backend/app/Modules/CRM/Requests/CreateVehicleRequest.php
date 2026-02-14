<?php

namespace App\Modules\CRM\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Create Vehicle Request
 * 
 * Validation rules for creating a new vehicle
 * 
 * @package App\Modules\CRM\Requests
 */
class CreateVehicleRequest extends FormRequest
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
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'customer_id' => 'required|integer|exists:customers,id',
            'make' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'vin' => 'nullable|string|max:17|unique:vehicles,vin',
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
            'tenant_id.exists' => 'The selected tenant does not exist.',
            'customer_id.exists' => 'The selected customer does not exist.',
            'vin.unique' => 'This VIN is already registered.',
        ];
    }
}
