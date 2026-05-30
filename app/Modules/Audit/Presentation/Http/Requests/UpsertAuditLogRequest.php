<?php

declare(strict_types=1);

namespace Modules\Audit\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertAuditLogRequest extends FormRequest
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
            'tenant_id' => ['nullable', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'row_version' => ['nullable', 'integer', 'min:0'],
            'metadata' => ['nullable', 'array'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'event' => array_merge($required, ['string', 'max:255']),
            'auditable_type' => array_merge($required, ['string', 'max:255']),
            'auditable_id' => array_merge($required, ['string', 'max:255']),
            'source_module' => ['nullable', 'string', 'max:100'],
            'source_type' => ['nullable', 'string', 'max:100'],
            'source_id' => ['nullable', 'string', 'max:255'],
            'source_reference' => ['nullable', 'string', 'max:255'],
            'source_context' => ['nullable', 'array'],
            'old_values' => ['nullable', 'array'],
            'new_values' => ['nullable', 'array'],
            'url' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['nullable', 'string', 'max:255'],
            'user_agent' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'occurred_at' => ['nullable', 'date'],
        ];
    }
}
