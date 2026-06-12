<?php

declare(strict_types=1);

namespace Modules\Extension\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'row_version' => ['required', 'integer', 'min:1'],
            'category' => [
                'sometimes',
                'string',
                Rule::in((array) config('extension.attachments.categories', [])),
            ],
            'display_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'document_number' => ['sometimes', 'nullable', 'string', 'max:150'],
            'tags' => ['sometimes', 'nullable', 'array', 'max:50'],
            'tags.*' => ['string', 'max:100'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'issued_at' => ['sometimes', 'nullable', 'date'],
            'expires_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:issued_at'],
        ];
    }
}
