<?php

declare(strict_types=1);

namespace Modules\Workflow\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartWorkflowInstanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'workflow_definition_id' => ['required', 'integer', 'min:1'],
            'entity_type' => ['required', 'string', 'max:100'],
            'entity_id' => ['required', 'integer', 'min:1'],
            'started_by_user_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
