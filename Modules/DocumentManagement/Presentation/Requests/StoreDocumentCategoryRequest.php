<?php

namespace Modules\DocumentManagement\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }
}
