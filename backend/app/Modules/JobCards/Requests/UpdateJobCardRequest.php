<?php

namespace App\Modules\JobCards\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $jobCardId = $this->route('id');

        return [
            'tenant_id' => 'sometimes|integer|exists:tenants,id',
            'appointment_id' => 'sometimes|nullable|integer|exists:appointments,id',
            'customer_id' => 'sometimes|integer|exists:customers,id',
            'vehicle_id' => 'sometimes|integer|exists:vehicles,id',
            'job_card_number' => 'sometimes|string|max:255|unique:job_cards,job_card_number,' . $jobCardId,
            'opened_date' => 'sometimes|date',
            'closed_date' => 'sometimes|nullable|date|after_or_equal:opened_date',
            'status' => 'sometimes|string|in:open,in_progress,completed,cancelled',
            'technician_id' => 'sometimes|nullable|integer|exists:users,id',
            'estimated_cost' => 'sometimes|numeric|min:0',
            'actual_cost' => 'sometimes|numeric|min:0',
            'notes' => 'sometimes|nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_id.exists' => 'The selected tenant does not exist.',
            'appointment_id.exists' => 'The selected appointment does not exist.',
            'customer_id.exists' => 'The selected customer does not exist.',
            'vehicle_id.exists' => 'The selected vehicle does not exist.',
            'technician_id.exists' => 'The selected technician does not exist.',
            'job_card_number.unique' => 'This job card number is already in use.',
            'status.in' => 'The status must be one of: open, in_progress, completed, cancelled.',
        ];
    }
}
