<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateServiceTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:2000'],
            'org_unit_id' => ['sometimes', 'integer', 'min:1'],
            'task_code' => ['sometimes', 'nullable', 'string', 'max:100'],
            'assigned_employee_id' => ['sometimes', 'integer', 'min:1'],
            'estimated_hours' => ['sometimes', 'numeric', 'min:0'],
            'labor_rate' => ['sometimes', 'numeric', 'min:0'],
            'metadata' => ['sometimes', 'array'],
        ];
    }
}
