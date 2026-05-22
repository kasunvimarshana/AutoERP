<?php

declare(strict_types=1);

namespace Modules\Configuration\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConfigurationRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(['country', 'currency', 'language', 'timezone'])],
            'payload' => ['required', 'array'],
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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $type = (string) $this->input('type');

            if (in_array($type, ['country', 'currency', 'language'], true) && !$this->filled('payload.code')) {
                $validator->errors()->add('payload.code', 'payload.code is required for selected type.');
            }

            if (!$this->filled('payload.name')) {
                $validator->errors()->add('payload.name', 'payload.name is required.');
            }

            if ($type === 'timezone' && !$this->filled('payload.offset')) {
                $validator->errors()->add('payload.offset', 'payload.offset is required for timezone.');
            }
        });
    }
}
