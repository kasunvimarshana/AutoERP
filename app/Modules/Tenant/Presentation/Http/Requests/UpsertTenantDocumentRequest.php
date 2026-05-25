<?php

declare(strict_types=1);

namespace Modules\Tenant\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertTenantDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => $this->isMethod('post') ? ['required', 'integer', 'min:1'] : ['sometimes', 'integer', 'min:1'],
            'name' => array_merge($required, ['string', 'max:255']),
            'file_path' => ['nullable', 'string', 'max:2048'],
            'file_upload' => ['nullable', 'file', 'max:10240'],
            'mime_type' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'integer', 'min:0'],
            'type' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
