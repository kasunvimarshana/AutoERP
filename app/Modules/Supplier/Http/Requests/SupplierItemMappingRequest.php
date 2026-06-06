<?php

declare(strict_types=1);

namespace Modules\Supplier\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Supplier\DTOs\SupplierItemMappingData;

abstract class SupplierItemMappingRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'item_id' => ['required', 'integer', 'min:1'],
            'item_variant_id' => ['nullable', 'integer', 'min:1'],
            'supplier_item_code' => ['nullable', 'string', 'max:150'],
            'supplier_item_name' => ['nullable', 'string', 'max:255'],
            'default_purchase_uom_id' => ['nullable', 'integer', 'min:1'],
            'minimum_order_quantity' => ['nullable', 'decimal:0,6', 'gte:0'],
            'lead_time_days' => ['nullable', 'integer', 'min:0'],
            'is_preferred' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function toData(): SupplierItemMappingData
    {
        return new SupplierItemMappingData(
            itemId: (int) $this->input('item_id'),
            itemVariantId: $this->filled('item_variant_id') ? (int) $this->input('item_variant_id') : null,
            supplierItemCode: $this->nullableString('supplier_item_code'),
            supplierItemName: $this->nullableString('supplier_item_name'),
            defaultPurchaseUomId: $this->filled('default_purchase_uom_id') ? (int) $this->input('default_purchase_uom_id') : null,
            minimumOrderQuantity: (string) $this->input('minimum_order_quantity', '0.000000'),
            leadTimeDays: $this->filled('lead_time_days') ? (int) $this->input('lead_time_days') : null,
            isPreferred: $this->boolean('is_preferred'),
            isActive: $this->boolean('is_active', true),
        );
    }

    private function nullableString(string $key): ?string
    {
        return $this->filled($key) ? (string) $this->input($key) : null;
    }
}
