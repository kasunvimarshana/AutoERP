<?php

namespace Modules\ProjectManagement\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTimeEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id'  => ['required', 'uuid'],
            'task_id'     => ['nullable', 'uuid'],
            'hours'       => ['required', 'numeric', 'min:0.25'],
            'description' => ['nullable', 'string'],
            'entry_date'  => ['required', 'date'],
        ];
    }
}
