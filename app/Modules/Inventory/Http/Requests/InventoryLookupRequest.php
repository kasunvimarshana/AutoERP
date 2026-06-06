<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class InventoryLookupRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'item_id' => ['nullable', 'integer', 'min:1'],
            'item_variant_id' => ['nullable', 'integer', 'min:1'],
            'warehouse_id' => ['nullable', 'integer', 'min:1'],
            'warehouse_location_id' => ['nullable', 'integer', 'min:1'],
            'batch_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', 'max:50'],
            'search' => ['nullable', 'string', 'max:150'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
