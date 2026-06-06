<?php

declare(strict_types=1);

namespace Modules\Supplier\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class AssignSupplierCategoryRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'category_id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function categoryId(): int
    {
        return (int) $this->input('category_id');
    }
}
