<?php

declare(strict_types=1);

namespace Modules\Extension\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Extension\Enums\AttachmentVisibility;

final class StoreAttachmentRequest extends FormRequest
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
            'attachable_type' => [
                'required',
                'string',
                Rule::in(array_keys((array) config('extension.attachments.attachables', []))),
            ],
            'attachable_id' => ['required', 'integer', 'min:1'],
            'category' => [
                'nullable',
                'string',
                Rule::in((array) config('extension.attachments.categories', [])),
            ],
            'visibility' => ['nullable', Rule::enum(AttachmentVisibility::class)],
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
