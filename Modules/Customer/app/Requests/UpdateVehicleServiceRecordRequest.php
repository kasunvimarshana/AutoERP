<?php

declare(strict_types=1);

namespace Modules\Customer\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Customer\Enums\ServiceStatus;
use Modules\Customer\Enums\ServiceType;

/**
 * Update Vehicle Service Record Request
 *
 * Validates data for updating an existing vehicle service record
 */
class UpdateVehicleServiceRecordRequest extends FormRequest
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
            'vehicle_id' => ['sometimes', 'integer', 'exists:vehicles,id'],
            'customer_id' => ['sometimes', 'integer', 'exists:customers,id'],
            'branch_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'service_date' => ['sometimes', 'date'],
            'mileage_at_service' => ['sometimes', 'integer', 'min:0'],
            'service_type' => ['sometimes', 'string', 'in:'.implode(',', ServiceType::values())],
            'service_description' => ['sometimes', 'nullable', 'string'],
            'parts_used' => ['sometimes', 'nullable', 'string'],
            'labor_cost' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:999999.99'],
            'parts_cost' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:999999.99'],
            'total_cost' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:999999.99'],
            'technician_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'technician_id' => ['sometimes', 'nullable', 'integer'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'next_service_mileage' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'next_service_date' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', 'string', 'in:'.implode(',', ServiceStatus::values())],
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
            'vehicle_id.exists' => 'The selected vehicle does not exist.',
            'customer_id.exists' => 'The selected customer does not exist.',
            'service_date.date' => 'The service date must be a valid date.',
            'mileage_at_service.integer' => 'The mileage must be a number.',
            'mileage_at_service.min' => 'The mileage cannot be negative.',
            'service_type.in' => 'The selected service type is invalid.',
            'labor_cost.numeric' => 'The labor cost must be a number.',
            'labor_cost.min' => 'The labor cost cannot be negative.',
            'parts_cost.numeric' => 'The parts cost must be a number.',
            'parts_cost.min' => 'The parts cost cannot be negative.',
            'status.in' => 'The selected status is invalid.',
        ];
    }

    /**
     * Prepare data for validation
     */
    protected function prepareForValidation(): void
    {
        // Recalculate total cost if labor or parts cost changed
        if (($this->has('labor_cost') || $this->has('parts_cost')) && ! $this->has('total_cost')) {
            $laborCost = $this->input('labor_cost', 0);
            $partsCost = $this->input('parts_cost', 0);

            $this->merge([
                'total_cost' => ($laborCost + $partsCost),
            ]);
        }
    }
}
