<?php

namespace App\Modules\JobCardManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJobCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'appointment_id' => 'nullable|exists:appointments,id',
            'customer_id' => 'required|exists:customers,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'service_bay_id' => 'nullable|exists:service_bays,id',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'current_mileage' => 'nullable|integer|min:0',
            'estimated_hours' => 'nullable|integer|min:0',
            'customer_complaint' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'internal_notes' => 'nullable|string',
            'estimated_cost' => 'nullable|numeric|min:0',
            'assigned_to' => 'nullable|exists:users,id',
        ];
    }
}
