<?php

declare(strict_types=1);

namespace Modules\Document\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'parent_folder_id' => ['nullable', 'string', 'exists:folders,id'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Folder name is required',
            'parent_folder_id.exists' => 'Parent folder does not exist',
        ];
    }
}
