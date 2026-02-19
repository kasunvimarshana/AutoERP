<?php

declare(strict_types=1);

namespace Modules\Workflow\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'code' => ['nullable', 'string', 'unique:workflows,code'],
            'trigger_type' => ['required', 'string', 'max:50'],
            'trigger_config' => ['nullable', 'array'],
            'entity_type' => ['nullable', 'string', 'max:255'],
            'is_template' => ['boolean'],
            'steps' => ['nullable', 'array'],
            'steps.*.name' => ['required', 'string'],
            'steps.*.type' => ['required', 'string'],
            'steps.*.sequence' => ['nullable', 'integer'],
            'steps.*.config' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
