<?php

declare(strict_types=1);

namespace Modules\Workflow\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateWorkflowDefinitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'entity_type' => ['required', 'string', 'max:100'],
            'states' => ['required', 'array', 'min:2'],
            'states.*.name' => ['required', 'string', 'max:255'],
            'states.*.description' => ['nullable', 'string'],
            'states.*.is_initial' => ['nullable', 'boolean'],
            'states.*.is_final' => ['nullable', 'boolean'],
            'states.*.sort_order' => ['nullable', 'integer'],
            'transitions' => ['required', 'array'],
            'transitions.*.name' => ['required', 'string', 'max:255'],
            'transitions.*.from_state_name' => ['required', 'string'],
            'transitions.*.to_state_name' => ['required', 'string'],
            'transitions.*.description' => ['nullable', 'string'],
            'transitions.*.requires_comment' => ['nullable', 'boolean'],
        ];
    }
}
