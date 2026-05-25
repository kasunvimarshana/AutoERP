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
        $filePathRules = ['nullable', 'string', 'max:2048'];
        $fileUploadRules = ['nullable', 'file', 'max:10240'];

        if ($this->isMethod('post')) {
            $filePathRules[] = 'required_without:file_upload';
            $fileUploadRules[] = 'required_without:file_path';
        }

        return [
            'tenant_id' => $this->isMethod('post')
                ? ['required', 'integer', 'min:1']
                : ['sometimes', 'integer', 'min:1'],
            'name' => array_merge($required, ['string', 'max:255']),
            'file_path' => $filePathRules,
            'file_upload' => $fileUploadRules,
            'mime_type' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'integer', 'min:0'],
            'type' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
