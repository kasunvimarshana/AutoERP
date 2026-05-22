<?php

declare(strict_types=1);

namespace Modules\Configuration\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConfigurationRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payload' => ['required', 'array'],
            'expected_row_version' => ['required', 'integer', 'min:1'],
            'payload.code' => ['nullable', 'string', 'max:20'],
            'payload.name' => ['nullable', 'string', 'max:190'],
            'payload.phone_code' => ['nullable', 'string', 'max:20'],
            'payload.symbol' => ['nullable', 'string', 'max:20'],
            'payload.decimal_places' => ['nullable', 'integer', 'between:0,6'],
            'payload.is_active' => ['nullable', 'boolean'],
            'payload.offset' => ['nullable', 'regex:/^[+\-](0[0-9]|1[0-4]):[0-5][0-9]$/'],
            'payload.metadata' => ['nullable', 'array'],
        ];
    }
}
