<?php

declare(strict_types=1);

namespace Modules\Extension\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListEntityAttributeRequest extends FormRequest
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
        return [
            'tenant_id' => ['nullable', 'integer', 'min:1', 'exists:tenants,id'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:' . (int) config('extension.pagination.max_per_page', 200)],
            'entity_type' => ['nullable', 'string', 'max:255'],
            'entity_id' => ['nullable', 'integer', 'min:1'],
            'attribute_key' => ['nullable', 'string', 'max:255']
        ];
    }
}