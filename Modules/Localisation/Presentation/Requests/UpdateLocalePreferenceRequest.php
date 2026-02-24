<?php

namespace Modules\Localisation\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLocalePreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'locale'        => ['required', 'string', 'max:20'],
            'timezone'      => ['required', 'string', 'max:60'],
            'date_format'   => ['nullable', 'string', 'max:20'],
            'number_format' => ['nullable', 'string', 'max:20'],
        ];
    }
}
