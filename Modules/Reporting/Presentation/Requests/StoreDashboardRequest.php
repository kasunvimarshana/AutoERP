<?php

namespace Modules\Reporting\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'            => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'layout'          => ['nullable', 'array'],
            'is_shared'       => ['boolean'],
            'refresh_seconds' => ['integer', 'min:30', 'max:86400'],
        ];
    }
}
