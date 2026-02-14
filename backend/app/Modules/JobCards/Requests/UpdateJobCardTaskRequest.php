<?php

namespace App\Modules\JobCards\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobCardTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'sometimes|integer|exists:tenants,id',
            'job_card_id' => 'sometimes|integer|exists:job_cards,id',
            'sku_id' => 'sometimes|nullable|integer|exists:skus,id',
            'task_type' => 'sometimes|string|in:service,part',
            'description' => 'sometimes|string',
            'quantity' => 'sometimes|numeric|min:0.01',
            'unit_price' => 'sometimes|numeric|min:0',
            'discount' => 'sometimes|nullable|numeric|min:0',
            'tax_rate' => 'sometimes|nullable|numeric|min:0',
            'line_total' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|string|in:pending,in_progress,completed',
            'completed_by' => 'sometimes|nullable|integer|exists:users,id',
            'completed_at' => 'sometimes|nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_id.exists' => 'The selected tenant does not exist.',
            'job_card_id.exists' => 'The selected job card does not exist.',
            'sku_id.exists' => 'The selected SKU does not exist.',
            'completed_by.exists' => 'The selected user does not exist.',
            'task_type.in' => 'The task type must be either service or part.',
            'status.in' => 'The status must be one of: pending, in_progress, completed.',
        ];
    }
}
