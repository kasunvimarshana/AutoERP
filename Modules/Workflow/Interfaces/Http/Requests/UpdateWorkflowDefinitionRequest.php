<?php

declare(strict_types=1);

namespace Modules\Workflow\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkflowDefinitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
