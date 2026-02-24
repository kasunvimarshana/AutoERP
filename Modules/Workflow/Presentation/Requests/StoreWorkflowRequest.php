<?php

namespace Modules\Workflow\Presentation\Requests;

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
            'name'          => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'document_type' => ['required', 'string', 'max:100'],
            'states'        => ['required', 'array', 'min:1'],
            'states.*'      => ['string'],
            'transitions'   => ['nullable', 'array'],
            'is_active'     => ['boolean'],
        ];
    }
}
