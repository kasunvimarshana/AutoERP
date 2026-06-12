<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertOrganizationUnitDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => $this->isMethod('post') ? ['required', 'integer', 'min:1'] : ['sometimes', 'integer', 'min:1'],
            'organization_unit_id' => array_merge($required, ['integer', 'min:1']),
            'name' => array_merge($required, ['string', 'max:255']),
            'file' => ['nullable', 'file'],
            'file_path' => $this->isMethod('post')
                ? ['required_without:file', 'nullable', 'string', 'max:2048']
                : ['sometimes', 'nullable', 'string', 'max:2048'],
            'mime_type' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'integer', 'min:0'],
            'type' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
