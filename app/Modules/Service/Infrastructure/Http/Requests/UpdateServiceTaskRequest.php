<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['sometimes', 'string', 'max:2000'],
            'task_code' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'string', 'in:pending,in_progress,completed,cancelled'],
            'assigned_employee_id' => ['sometimes', 'integer', 'min:1'],
            'estimated_hours' => ['sometimes', 'numeric', 'min:0'],
            'actual_hours' => ['sometimes', 'numeric', 'min:0'],
            'labor_rate' => ['sometimes', 'numeric', 'min:0'],
            'labor_amount' => ['sometimes', 'numeric', 'min:0'],
            'commission_amount' => ['sometimes', 'numeric', 'min:0'],
            'incentive_amount' => ['sometimes', 'numeric', 'min:0'],
            'completed_at' => ['sometimes', 'nullable', 'date'],
            'metadata' => ['sometimes', 'array'],
        ];
    }
}
