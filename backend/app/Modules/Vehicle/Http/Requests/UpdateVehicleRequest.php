<?php

namespace App\Modules\Vehicle\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $vehicleId = $this->route('vehicle');
        
        return [
            'tenant_id' => ['sometimes', 'string', 'exists:tenants,id'],
            'customer_id' => ['sometimes', 'string', 'exists:customers,id'],
            'branch_id' => ['sometimes', 'string', 'exists:organizations,id'],
            'vin' => ['nullable', 'string', 'max:17', Rule::unique('vehicles', 'vin')->ignore($vehicleId)],
            'registration_number' => ['sometimes', 'string', 'max:50', Rule::unique('vehicles', 'registration_number')->ignore($vehicleId)],
            'make' => ['sometimes', 'string', 'max:100'],
            'model' => ['sometimes', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'color' => ['nullable', 'string', 'max:50'],
            'fuel_type' => ['nullable', 'string', 'max:50'],
            'transmission_type' => ['nullable', 'string', 'max:50'],
            'engine_number' => ['nullable', 'string', 'max:50'],
            'chassis_number' => ['nullable', 'string', 'max:50'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'purchase_date' => ['nullable', 'date'],
            'last_service_date' => ['nullable', 'date'],
            'next_service_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'notes' => ['nullable', 'string'],
            'settings' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_id.exists' => 'The selected tenant does not exist',
            'customer_id.exists' => 'The selected customer does not exist',
            'branch_id.exists' => 'The selected branch does not exist',
            'vin.unique' => 'This VIN is already registered',
            'registration_number.unique' => 'This registration number is already registered',
            'year.min' => 'Year must be 1900 or later',
            'year.max' => 'Year cannot be more than next year',
            'mileage.min' => 'Mileage cannot be negative',
            'status.in' => 'Status must be either active or inactive',
        ];
    }
}
