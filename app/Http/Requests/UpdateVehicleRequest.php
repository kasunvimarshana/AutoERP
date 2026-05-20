<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVehicleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $vehicle = $this->route('vehicle');

        return [
            'tenant_id' => 'sometimes|nullable|integer|exists:tenants,id',
            'organization_unit_id' => 'sometimes|nullable|integer|exists:organization_units,id',

            'vehicle_code' => 'required|string',
            'vin' => 'required|string',
            'license_plate' => 'required|string',
            'make' => 'required|string',
            'model' => 'required|string',
            'year' => 'required|integer',
            'color' => 'required|string',
            'category' => 'required|string',
            'usage_profile' => 'required|string',
            'fuel_type' => 'required|string',
            'transmission' => 'required|string',
            'seating_capacity' => 'required|integer',
            'current_odometer' => 'required|integer',
            'status' => 'required|string',
            'registration_expiry' => 'required|date',
            'insurance_expiry' => 'required|date',
            'last_service_date' => 'required|date',
            'last_service_odometer' => 'required|integer',
            'next_service_due_date' => 'required|date',
            'next_service_due_odometer' => 'required|integer',
        ];
    }
}
