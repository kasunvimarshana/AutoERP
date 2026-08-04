<?php

declare(strict_types=1);

namespace Modules\Item\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Item\DTOs\ItemBundleData;
use Modules\VehicleService\Enums\VehicleServiceWorkforceRole;

abstract class ItemBundleRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'child_item_id' => ['required', 'integer', 'min:1'],
            'child_variant_id' => ['nullable', 'integer', 'min:1'],
            'quantity' => ['required', 'decimal:0,6', 'gt:0'],
            'uom_id' => ['nullable', 'integer', 'min:1'],
            'line_type' => ['required', Rule::in(['stock', 'service', 'labour', 'non_stock', 'charge'])],
            'unit_cost' => ['nullable', 'decimal:0,6', 'min:0'],
            'default_workforce_role' => ['nullable', Rule::enum(VehicleServiceWorkforceRole::class)],
            'is_required' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function toData(): ItemBundleData
    {
        return new ItemBundleData(
            childItemId: (int) $this->input('child_item_id'),
            quantity: (string) $this->input('quantity'),
            lineType: (string) $this->input('line_type'),
            childVariantId: $this->filled('child_variant_id') ? (int) $this->input('child_variant_id') : null,
            uomId: $this->filled('uom_id') ? (int) $this->input('uom_id') : null,
            isRequired: $this->boolean('is_required', true),
            sortOrder: (int) $this->input('sort_order', 0),
            unitCost: (string) $this->input('unit_cost', '0.000000'),
            defaultWorkforceRole: $this->filled('default_workforce_role')
                ? (string) $this->input('default_workforce_role')
                : null,
        );
    }
}
