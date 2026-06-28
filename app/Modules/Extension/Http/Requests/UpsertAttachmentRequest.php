<?php

declare(strict_types=1);

namespace Modules\Extension\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;

final class UpsertAttachmentRequest extends TenantScopedRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];
        $entityTypes = $this->allowedEntityTypes();

        return [
            'row_version' => ['nullable', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', $this->tenantExists('organization_units')],
            'metadata' => ['nullable', 'array'],
            'attachable_type' => [...$required, 'string', Rule::in($entityTypes)],
            'attachable_id' => [...$required, 'integer', 'min:1'],
            'source_module' => ['nullable', 'string', 'max:100'],
            'source_type' => ['nullable', 'required_with:source_id', 'string', Rule::in($entityTypes)],
            'source_id' => ['nullable', 'required_with:source_type', 'integer', 'min:1'],
            'source_reference' => ['nullable', 'string', 'max:255'],
            'source_context' => ['nullable', 'array'],
            'file_name' => [...$required, 'string', 'max:255'],
            'file_path' => [...$required, 'string', 'max:255'],
            'mime_type' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'integer', 'min:0'],
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
