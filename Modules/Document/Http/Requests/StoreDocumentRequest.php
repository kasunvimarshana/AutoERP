<?php

declare(strict_types=1);

namespace Modules\Document\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Document\Enums\AccessLevel;
use Modules\Document\Enums\DocumentStatus;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxSize = config('document.upload.max_size', 10240);
        $allowedMimes = config('document.upload.allowed_mimes', []);

        $rules = [
            'file' => ['required', 'file', "max:{$maxSize}"],
            'folder_id' => ['nullable', 'string', 'exists:folders,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'string', 'in:'.implode(',', array_column(DocumentStatus::cases(), 'value'))],
            'access_level' => ['nullable', 'string', 'in:'.implode(',', array_column(AccessLevel::cases(), 'value'))],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:100'],
        ];

        if (! empty($allowedMimes)) {
            $rules['file'][] = 'mimes:'.implode(',', array_map(fn ($mime) => explode('/', $mime)[1] ?? $mime, $allowedMimes));
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'file.required' => 'File is required',
            'file.max' => 'File size must not exceed '.(config('document.upload.max_size', 10240) / 1024).'MB',
            'folder_id.exists' => 'Selected folder does not exist',
        ];
    }
}
