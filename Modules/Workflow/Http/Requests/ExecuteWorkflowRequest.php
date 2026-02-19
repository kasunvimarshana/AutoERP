<?php

declare(strict_types=1);

namespace Modules\Workflow\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExecuteWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'context' => ['required', 'array'],
            'entity_type' => ['nullable', 'string'],
            'entity_id' => ['nullable', 'integer'],
        ];
    }
}
