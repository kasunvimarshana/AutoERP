<?php

declare(strict_types=1);

namespace Modules\Customer\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Customer\Enums\ServiceStatus;
use Modules\Customer\Enums\ServiceType;

/**
 * Store Vehicle Service Record Request
 *
 * Validates data for creating a new vehicle service record
 */
class StoreVehicleServiceRecordRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'branch_id' => ['nullable', 'string', 'max:255'],
            'service_date' => ['required', 'date'],
            'mileage_at_service' => ['required', 'integer', 'min:0'],
            'service_type' => ['required', 'string', 'in:'.implode(',', ServiceType::values())],
            'service_description' => ['nullable', 'string'],
            'parts_used' => ['nullable', 'string'],
            'labor_cost' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'parts_cost' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'total_cost' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'technician_name' => ['nullable', 'string', 'max:255'],
            'technician_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
            'next_service_mileage' => ['nullable', 'integer', 'min:0'],
            'next_service_date' => ['nullable', 'date', 'after:service_date'],
            'status' => ['nullable', 'string', 'in:'.implode(',', ServiceStatus::values())],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'vehicle_id.required' => 'The vehicle field is required.',
            'vehicle_id.exists' => 'The selected vehicle does not exist.',
            'customer_id.required' => 'The customer field is required.',
            'customer_id.exists' => 'The selected customer does not exist.',
            'service_date.required' => 'The service date is required.',
            'service_date.date' => 'The service date must be a valid date.',
            'mileage_at_service.required' => 'The mileage at service is required.',
            'mileage_at_service.integer' => 'The mileage must be a number.',
            'mileage_at_service.min' => 'The mileage cannot be negative.',
            'service_type.required' => 'The service type is required.',
            'service_type.in' => 'The selected service type is invalid.',
            'labor_cost.numeric' => 'The labor cost must be a number.',
            'labor_cost.min' => 'The labor cost cannot be negative.',
            'parts_cost.numeric' => 'The parts cost must be a number.',
            'parts_cost.min' => 'The parts cost cannot be negative.',
            'next_service_date.after' => 'The next service date must be after the service date.',
            'status.in' => 'The selected status is invalid.',
        ];
    }

    /**
     * Prepare data for validation
     */
    protected function prepareForValidation(): void
    {
        // Set default status if not provided
        if (! $this->has('status')) {
            $this->merge([
                'status' => ServiceStatus::COMPLETED->value,
            ]);
        }

        // Calculate total cost if not provided
        if (! $this->has('total_cost') && ($this->has('labor_cost') || $this->has('parts_cost'))) {
            $this->merge([
                'total_cost' => ($this->input('labor_cost', 0) + $this->input('parts_cost', 0)),
            ]);
        }
    }
}
