<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertUserDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];
        $mimeTypes = config('extension.attachments.allowed_mime_types', []);

        return [
            'metadata' => ['nullable', 'array'],
            'user_id' => array_merge($required, ['integer', 'min:1', 'exists:users,id']),
            'name' => array_merge($required, ['string', 'max:255']),
            'file' => [
                $this->isMethod('post') ? 'required' : 'nullable',
                'file',
                'max:'.(int) config('extension.attachments.max_upload_kilobytes', 51200),
                'mimetypes:'.implode(',', is_array($mimeTypes) ? $mimeTypes : []),
            ],
            'row_version' => $this->isMethod('post')
                ? ['nullable']
                : ['required', 'integer', 'min:1'],
            'type' => ['nullable', 'string', 'max:100'],
        ];
    }
}
