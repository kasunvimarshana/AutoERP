<?php

declare(strict_types=1);

namespace Modules\Workflow\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'trigger_type' => ['sometimes', 'string', 'max:50'],
            'trigger_config' => ['nullable', 'array'],
            'entity_type' => ['nullable', 'string', 'max:255'],
            'steps' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
