<?php

declare(strict_types=1);

namespace Modules\Extension\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreAttachmentVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $mimeTypes = config('extension.attachments.allowed_mime_types', []);

        return [
            'file' => [
                'required',
                'file',
                'max:'.(int) config('extension.attachments.max_upload_kilobytes', 51200),
                'mimetypes:'.implode(',', is_array($mimeTypes) ? $mimeTypes : []),
            ],
            'category' => [
                'nullable',
                'string',
                Rule::in((array) config('extension.attachments.categories', [])),
            ],
            'display_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'document_number' => ['nullable', 'string', 'max:150'],
            'tags' => ['nullable', 'array', 'max:50'],
            'tags.*' => ['string', 'max:100'],
            'metadata' => ['nullable', 'array'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
        ];
    }
}
