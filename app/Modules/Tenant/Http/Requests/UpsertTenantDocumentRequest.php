<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertTenantDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];
        $mimeTypes = config('extension.attachments.allowed_mime_types', []);

        return [
            'tenant_id' => $this->isMethod('post')
                ? ['required', 'integer', 'min:1']
                : ['sometimes', 'integer', 'min:1'],
            'name' => array_merge($required, ['string', 'max:255']),
            'file_upload' => [
                $this->isMethod('post') ? 'required' : 'nullable',
                'file',
                'max:'.(int) config('extension.attachments.max_upload_kilobytes', 51200),
                'mimetypes:'.implode(',', is_array($mimeTypes) ? $mimeTypes : []),
            ],
            'row_version' => $this->isMethod('post')
                ? ['nullable']
                : ['required', 'integer', 'min:1'],
            'type' => ['nullable', 'string', 'max:100'],
            'is_public' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
