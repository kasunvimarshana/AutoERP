<?php

declare(strict_types=1);

namespace Modules\Configuration\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('currencies', 'code')->ignore($this->route('currency'))],
            'name' => ['required', 'string', 'max:255'],
            'symbol' => ['nullable', 'string', 'max:255'],
            'decimal_places' => ['nullable', 'integer', 'min:0', 'max:8'],
            'is_active' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
