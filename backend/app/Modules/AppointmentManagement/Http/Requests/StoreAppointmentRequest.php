<?php

namespace App\Modules\AppointmentManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'service_bay_id' => 'nullable|exists:service_bays,id',
            'scheduled_date' => 'required|date|after_or_equal:today',
            'scheduled_time' => 'required|date_format:H:i',
            'estimated_duration' => 'nullable|integer|min:15',
            'appointment_type' => 'required|in:routine_service,repair,inspection,diagnostic,custom',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'service_description' => 'nullable|string',
            'customer_notes' => 'nullable|string',
            'internal_notes' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
        ];
    }
}
