<?php

namespace App\Modules\CustomerManagement\Http\Requests;

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
            'current_customer_id' => 'required|exists:customers,id',
            'vin' => 'required|string|max:17|unique:vehicles,vin',
            'registration_number' => 'required|string|max:20|unique:vehicles,registration_number',
            'make' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'color' => 'nullable|string|max:50',
            'engine_number' => 'nullable|string|max:50',
            'chassis_number' => 'nullable|string|max:50',
            'vehicle_type' => 'required|in:car,truck,motorcycle,suv,van,bus,other',
            'fuel_type' => 'nullable|string|max:50',
            'transmission' => 'nullable|string|max:50',
            'engine_capacity' => 'nullable|integer',
            'current_mileage' => 'nullable|numeric|min:0',
            'mileage_unit' => 'nullable|in:km,miles',
            'service_interval_days' => 'nullable|integer|min:1',
            'service_interval_mileage' => 'nullable|integer|min:1',
            'insurance_provider' => 'nullable|string|max:100',
            'insurance_policy_number' => 'nullable|string|max:100',
            'insurance_expiry_date' => 'nullable|date',
            'registration_expiry_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ];
    }
}
