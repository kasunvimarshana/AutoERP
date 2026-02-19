<?php

declare(strict_types=1);

namespace Modules\Customer\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Vehicle Request
 *
 * Validates data for creating a new vehicle
 */
class StoreVehicleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware/policies
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'vehicle_number' => ['sometimes', 'string', 'max:50', 'unique:vehicles,vehicle_number'],
            'registration_number' => ['required', 'string', 'max:50', 'unique:vehicles,registration_number'],
            'vin' => ['nullable', 'string', 'max:17', 'unique:vehicles,vin'],
            'make' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'year' => ['required', 'integer', 'min:1900', 'max:'.(date('Y') + 1)],
            'color' => ['nullable', 'string', 'max:50'],
            'engine_number' => ['nullable', 'string', 'max:50'],
            'chassis_number' => ['nullable', 'string', 'max:50'],
            'fuel_type' => ['nullable', Rule::in(['petrol', 'diesel', 'electric', 'hybrid', 'lpg', 'cng'])],
            'transmission' => ['nullable', Rule::in(['manual', 'automatic', 'cvt', 'dct'])],
            'current_mileage' => ['sometimes', 'integer', 'min:0'],
            'purchase_date' => ['nullable', 'date', 'before_or_equal:today'],
            'registration_date' => ['nullable', 'date', 'before_or_equal:today'],
            'insurance_expiry' => ['nullable', 'date'],
            'insurance_provider' => ['nullable', 'string', 'max:255'],
            'insurance_policy_number' => ['nullable', 'string', 'max:100'],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'sold', 'scrapped'])],
            'notes' => ['nullable', 'string'],
            'last_service_date' => ['nullable', 'date', 'before_or_equal:today'],
            'next_service_mileage' => ['nullable', 'integer', 'min:0'],
            'next_service_date' => ['nullable', 'date'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'customer_id' => 'customer',
            'vehicle_number' => 'vehicle number',
            'registration_number' => 'registration number',
            'vin' => 'VIN',
            'engine_number' => 'engine number',
            'chassis_number' => 'chassis number',
            'fuel_type' => 'fuel type',
            'current_mileage' => 'current mileage',
            'purchase_date' => 'purchase date',
            'registration_date' => 'registration date',
            'insurance_expiry' => 'insurance expiry',
            'insurance_provider' => 'insurance provider',
            'insurance_policy_number' => 'insurance policy number',
            'last_service_date' => 'last service date',
            'next_service_mileage' => 'next service mileage',
            'next_service_date' => 'next service date',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_id.exists' => 'The selected customer does not exist.',
            'registration_number.unique' => 'This registration number is already registered.',
            'vin.unique' => 'This VIN is already registered.',
            'vehicle_number.unique' => 'This vehicle number is already in use.',
            'year.max' => 'The year cannot be greater than next year.',
            'purchase_date.before_or_equal' => 'The purchase date cannot be in the future.',
            'registration_date.before_or_equal' => 'The registration date cannot be in the future.',
            'last_service_date.before_or_equal' => 'The last service date cannot be in the future.',
        ];
    }
}
