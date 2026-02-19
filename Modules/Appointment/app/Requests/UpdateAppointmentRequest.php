<?php

declare(strict_types=1);

namespace Modules\Appointment\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Appointment\Enums\AppointmentStatus;
use Modules\Appointment\Enums\ServiceType;

/**
 * Update Appointment Request
 *
 * Validates data for updating an appointment
 */
class UpdateAppointmentRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['sometimes', 'integer', 'exists:customers,id'],
            'vehicle_id' => ['sometimes', 'integer', 'exists:vehicles,id'],
            'branch_id' => ['sometimes', 'integer', 'exists:branches,id'],
            'service_type' => ['sometimes', Rule::in(ServiceType::values())],
            'scheduled_date_time' => ['sometimes', 'date'],
            'duration' => ['sometimes', 'integer', 'min:15', 'max:480'],
            'status' => ['sometimes', Rule::in(AppointmentStatus::values())],
            'notes' => ['nullable', 'string'],
            'customer_notes' => ['nullable', 'string'],
            'assigned_technician_id' => ['nullable', 'integer', 'exists:users,id'],
            'cancellation_reason' => ['nullable', 'string'],
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
            'vehicle_id' => 'vehicle',
            'branch_id' => 'branch',
            'service_type' => 'service type',
            'scheduled_date_time' => 'scheduled date and time',
            'assigned_technician_id' => 'assigned technician',
            'customer_notes' => 'customer notes',
            'cancellation_reason' => 'cancellation reason',
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
            'duration.min' => 'The appointment duration must be at least 15 minutes.',
            'duration.max' => 'The appointment duration cannot exceed 8 hours.',
        ];
    }
}
