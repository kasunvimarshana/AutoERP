<?php

declare(strict_types=1);

namespace Modules\Workflow\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelWorkflowInstanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'actor_user_id' => ['required', 'integer', 'min:1'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
