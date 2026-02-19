<?php

declare(strict_types=1);

namespace Modules\Appointment\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Appointment\Enums\AppointmentStatus;
use Modules\Appointment\Enums\ServiceType;

/**
 * Store Appointment Request
 *
 * Validates data for creating a new appointment
 */
class StoreAppointmentRequest extends FormRequest
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
            'appointment_number' => ['sometimes', 'string', 'max:50', 'unique:appointments,appointment_number'],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'service_type' => ['required', Rule::in(ServiceType::values())],
            'scheduled_date_time' => ['required', 'date', 'after:now'],
            'duration' => ['required', 'integer', 'min:15', 'max:480'],
            'status' => ['sometimes', Rule::in(AppointmentStatus::values())],
            'notes' => ['nullable', 'string'],
            'customer_notes' => ['nullable', 'string'],
            'assigned_technician_id' => ['nullable', 'integer', 'exists:users,id'],
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
            'appointment_number' => 'appointment number',
            'customer_id' => 'customer',
            'vehicle_id' => 'vehicle',
            'branch_id' => 'branch',
            'service_type' => 'service type',
            'scheduled_date_time' => 'scheduled date and time',
            'assigned_technician_id' => 'assigned technician',
            'customer_notes' => 'customer notes',
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
            'appointment_number.unique' => 'This appointment number is already in use.',
            'scheduled_date_time.after' => 'The appointment must be scheduled for a future date and time.',
            'duration.min' => 'The appointment duration must be at least 15 minutes.',
            'duration.max' => 'The appointment duration cannot exceed 8 hours.',
        ];
    }
}
