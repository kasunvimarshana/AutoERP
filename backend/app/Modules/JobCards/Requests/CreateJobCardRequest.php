<?php

namespace App\Modules\JobCards\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateJobCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'appointment_id' => 'nullable|integer|exists:appointments,id',
            'customer_id' => 'required|integer|exists:customers,id',
            'vehicle_id' => 'required|integer|exists:vehicles,id',
            'job_card_number' => 'required|string|max:255|unique:job_cards,job_card_number',
            'opened_date' => 'required|date',
            'closed_date' => 'nullable|date|after_or_equal:opened_date',
            'status' => 'nullable|string|in:open,in_progress,completed,cancelled',
            'technician_id' => 'nullable|integer|exists:users,id',
            'estimated_cost' => 'nullable|numeric|min:0',
            'actual_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'tasks' => 'nullable|array',
            'tasks.*.sku_id' => 'nullable|integer|exists:skus,id',
            'tasks.*.task_type' => 'required|string|in:service,part',
            'tasks.*.description' => 'required|string',
            'tasks.*.quantity' => 'required|numeric|min:0.01',
            'tasks.*.unit_price' => 'required|numeric|min:0',
            'tasks.*.discount' => 'nullable|numeric|min:0',
            'tasks.*.tax_rate' => 'nullable|numeric|min:0',
            'tasks.*.line_total' => 'required|numeric|min:0',
            'tasks.*.status' => 'nullable|string|in:pending,in_progress,completed',
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
            'tasks.*.task_type.in' => 'The task type must be either service or part.',
            'tasks.*.status.in' => 'The task status must be one of: pending, in_progress, completed.',
        ];
    }
}
