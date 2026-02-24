<?php

namespace Modules\ProjectManagement\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id'       => ['required', 'uuid'],
            'title'            => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'assigned_to'      => ['nullable', 'uuid'],
            'status'           => ['nullable', 'string', 'in:todo,in_progress,review,done'],
            'priority'         => ['nullable', 'string', 'in:low,medium,high,critical'],
            'due_date'         => ['nullable', 'date'],
            'estimated_hours'  => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
