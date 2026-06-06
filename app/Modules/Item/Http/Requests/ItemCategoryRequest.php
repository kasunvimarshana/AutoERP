<?php

declare(strict_types=1);

namespace Modules\Item\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

abstract class ItemCategoryRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'parent_id' => ['nullable', 'integer', 'min:1'],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function payload(): array
    {
        return [
            'parent_id' => $this->filled('parent_id') ? (int) $this->input('parent_id') : null,
            'code' => (string) $this->input('code'),
            'name' => (string) $this->input('name'),
            'description' => $this->filled('description') ? (string) $this->input('description') : null,
            'is_active' => $this->boolean('is_active', true),
            'sort_order' => (int) $this->input('sort_order', 0),
        ];
    }
}
