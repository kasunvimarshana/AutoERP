<?php

namespace App\Modules\Appointments\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AppointmentRequest extends FormRequest
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
            'scheduled_date' => 'required|date|after_or_equal:' . now()->toDateString(),
            'scheduled_time' => 'required|date_format:H:i',
            'duration_minutes' => 'required|integer|min:15|max:480',
            'service_type' => 'required|string|max:255',
            'status' => 'sometimes|in:scheduled,confirmed,in_progress,completed,cancelled,no_show',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Customer is required',
            'customer_id.exists' => 'Selected customer does not exist',
            'vehicle_id.required' => 'Vehicle is required',
            'vehicle_id.exists' => 'Selected vehicle does not exist',
            'scheduled_date.required' => 'Scheduled date is required',
            'scheduled_date.after_or_equal' => 'Scheduled date must be today or later',
            'scheduled_time.required' => 'Scheduled time is required',
            'duration_minutes.required' => 'Duration is required',
            'duration_minutes.min' => 'Duration must be at least 15 minutes',
            'duration_minutes.max' => 'Duration cannot exceed 8 hours',
            'service_type.required' => 'Service type is required',
        ];
    }
}
