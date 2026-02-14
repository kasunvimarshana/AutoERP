<?php

namespace App\Modules\Fleet\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFleetVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        
        return [
            'tenant_id' => 'sometimes|required|integer|exists:tenants,id',
            'vehicle_number' => 'sometimes|required|string|max:255|unique:fleet_vehicles,vehicle_number,' . $id,
            'make' => 'sometimes|required|string|max:255',
            'model' => 'sometimes|required|string|max:255',
            'year' => 'sometimes|required|integer|min:1900|max:' . (date('Y') + 1),
            'vin' => 'nullable|string|max:17|unique:fleet_vehicles,vin,' . $id,
            'license_plate' => 'sometimes|required|string|max:255|unique:fleet_vehicles,license_plate,' . $id,
            'color' => 'nullable|string|max:255',
            'mileage' => 'nullable|integer|min:0',
            'fuel_type' => 'sometimes|required|string|in:gasoline,diesel,electric,hybrid',
            'status' => 'nullable|string|in:active,maintenance,retired',
            'last_service_date' => 'nullable|date',
            'next_service_due' => 'nullable|date',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_id.exists' => 'The selected tenant does not exist.',
            'vehicle_number.unique' => 'This vehicle number is already in use.',
            'vin.unique' => 'This VIN is already registered.',
            'license_plate.unique' => 'This license plate is already registered.',
            'fuel_type.in' => 'The fuel type must be one of: gasoline, diesel, electric, hybrid.',
            'status.in' => 'The status must be one of: active, maintenance, retired.',
        ];
    }
}
