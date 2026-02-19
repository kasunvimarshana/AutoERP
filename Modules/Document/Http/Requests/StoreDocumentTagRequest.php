<?php

declare(strict_types=1);

namespace Modules\Document\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tag name is required',
        ];
    }
}
