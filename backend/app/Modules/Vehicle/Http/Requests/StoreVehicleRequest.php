<?php

namespace App\Modules\Vehicle\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'string', 'exists:tenants,id'],
            'customer_id' => ['required', 'string', 'exists:customers,id'],
            'branch_id' => ['required', 'string', 'exists:organizations,id'],
            'vin' => ['nullable', 'string', 'max:17', 'unique:vehicles,vin'],
            'registration_number' => ['required', 'string', 'max:50', 'unique:vehicles,registration_number'],
            'make' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'color' => ['nullable', 'string', 'max:50'],
            'fuel_type' => ['nullable', 'string', 'max:50'],
            'transmission_type' => ['nullable', 'string', 'max:50'],
            'engine_number' => ['nullable', 'string', 'max:50'],
            'chassis_number' => ['nullable', 'string', 'max:50'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'purchase_date' => ['nullable', 'date'],
            'last_service_date' => ['nullable', 'date'],
            'next_service_date' => ['nullable', 'date', 'after_or_equal:today'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'notes' => ['nullable', 'string'],
            'settings' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_id.required' => 'Tenant ID is required',
            'tenant_id.exists' => 'The selected tenant does not exist',
            'customer_id.required' => 'Customer ID is required',
            'customer_id.exists' => 'The selected customer does not exist',
            'branch_id.required' => 'Branch ID is required',
            'branch_id.exists' => 'The selected branch does not exist',
            'vin.unique' => 'This VIN is already registered',
            'registration_number.required' => 'Registration number is required',
            'registration_number.unique' => 'This registration number is already registered',
            'make.required' => 'Vehicle make is required',
            'model.required' => 'Vehicle model is required',
            'year.min' => 'Year must be 1900 or later',
            'year.max' => 'Year cannot be more than next year',
            'mileage.min' => 'Mileage cannot be negative',
            'next_service_date.after_or_equal' => 'Next service date must be today or in the future',
            'status.in' => 'Status must be either active or inactive',
        ];
    }
}
