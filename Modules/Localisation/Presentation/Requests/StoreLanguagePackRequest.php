<?php

namespace Modules\Localisation\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLanguagePackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'locale'    => ['required', 'string', 'max:20'],
            'name'      => ['required', 'string', 'max:255'],
            'direction' => ['in:ltr,rtl'],
            'strings'   => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ];
    }
}
