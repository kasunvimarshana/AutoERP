<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateServiceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'asset_id' => ['required', 'uuid'],
            'assigned_technician_id' => ['nullable', 'uuid'],
            'service_type' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'scheduled_at' => ['nullable', 'date'],
            'estimated_cost' => ['required', 'numeric', 'min:0'],
            'tasks' => ['sometimes', 'array'],
            'tasks.*.task_name' => ['required_with:tasks', 'string', 'max:255'],
            'tasks.*.description' => ['nullable', 'string'],
            'tasks.*.labor_cost' => ['required_with:tasks', 'numeric', 'min:0'],
            'tasks.*.labor_minutes' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
