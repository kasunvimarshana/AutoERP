<?php

declare(strict_types=1);

namespace Modules\Supplier\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Supplier\DTOs\SupplierCategoryData;

abstract class SupplierCategoryRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'parent_id' => ['nullable', 'integer', 'min:1'],
            'code' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function toData(): SupplierCategoryData
    {
        return new SupplierCategoryData(
            tenantId: $this->tenantId(),
            code: (string) $this->input('code'),
            name: (string) $this->input('name'),
            organizationUnitId: $this->organizationUnitId(),
            parentId: $this->filled('parent_id') ? (int) $this->input('parent_id') : null,
            description: $this->filled('description') ? (string) $this->input('description') : null,
            isActive: $this->boolean('is_active', true),
            sortOrder: (int) $this->input('sort_order', 0),
        );
    }
}
