<?php

namespace Modules\ProjectManagement\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMilestoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'uuid'],
            'name'       => ['required', 'string', 'max:255'],
            'due_date'   => ['required', 'date'],
            'status'     => ['nullable', 'string', 'in:pending,in_progress,completed,cancelled'],
        ];
    }
}
