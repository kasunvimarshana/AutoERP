<?php

declare(strict_types=1);

namespace Modules\Extension\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;

final class UpsertEntityAttributeRequest extends TenantScopedRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'row_version' => ['nullable', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', $this->tenantExists('organization_units')],
            'metadata' => ['nullable', 'array'],
            'entity_type' => [...$required, 'string', Rule::in($this->allowedEntityTypes())],
            'entity_id' => [...$required, 'integer', 'min:1'],
            'attribute_key' => [...$required, 'string', 'max:255'],
            'attribute_value' => ['nullable', 'string'],
        ];
    }

    private function tenantExists(string $table): mixed
    {
        return Rule::exists($table, 'id')->where(
            fn ($query) => $query->where('tenant_id', $this->tenantId()),
        );
    }

    /** @return list<string> */
    private function allowedEntityTypes(): array
    {
        return array_map('strval', array_keys((array) config('extension.entity_types', [])));
    }
}
