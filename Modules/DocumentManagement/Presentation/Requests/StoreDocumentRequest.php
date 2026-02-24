<?php

namespace Modules\DocumentManagement\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'uuid'],
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'file_path'   => ['nullable', 'string', 'max:500'],
            'mime_type'   => ['nullable', 'string', 'max:100'],
            'file_size'   => ['nullable', 'integer', 'min:0'],
        ];
    }
}
