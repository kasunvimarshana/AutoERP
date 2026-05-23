<?php

declare(strict_types=1);

namespace Modules\Configuration\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLanguageRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:255', Rule::unique('languages', 'code')->ignore($this->route('language'))],
            'name' => ['required', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
