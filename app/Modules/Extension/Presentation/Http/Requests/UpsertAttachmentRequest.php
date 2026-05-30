<?php

declare(strict_types=1);

namespace Modules\Extension\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertAttachmentRequest extends FormRequest
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

        return [
            'tenant_id' => array_merge($required, ['integer', 'min:1', 'exists:tenants,id']),
            'row_version' => ['nullable', 'integer', 'min:0'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'metadata' => ['nullable', 'array'],
            'attachable_type' => array_merge($required, ['string', 'max:255']),
            'attachable_id' => array_merge($required, ['integer', 'min:1']),
            'source_module' => ['nullable', 'string', 'max:100'],
            'source_type' => ['nullable', 'string', 'max:100'],
            'source_id' => ['nullable', 'integer', 'min:1'],
            'source_reference' => ['nullable', 'string', 'max:255'],
            'source_context' => ['nullable', 'array'],
            'file_name' => array_merge($required, ['string', 'max:255']),
            'file_path' => array_merge($required, ['string', 'max:255']),
            'mime_type' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
