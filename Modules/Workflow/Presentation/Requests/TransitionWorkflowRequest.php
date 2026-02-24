<?php

namespace Modules\Workflow\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransitionWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_type' => ['required', 'string', 'max:100'],
            'document_id'   => ['required', 'string'],
            'from_state'    => ['required', 'string', 'max:100'],
            'to_state'      => ['required', 'string', 'max:100'],
            'comment'       => ['nullable', 'string'],
        ];
    }
}
