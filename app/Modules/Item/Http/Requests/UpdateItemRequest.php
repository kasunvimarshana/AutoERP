<?php

declare(strict_types=1);

namespace Modules\Item\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Item\DTOs\UpdateItemData;
use Modules\Item\Enums\CostingMethod;
use Modules\Item\Enums\ItemType;
use Modules\Item\Enums\TrackingType;

final class UpdateItemRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'code' => ['sometimes', 'string', 'max:80'],
            'name' => ['sometimes', 'string', 'max:255'],
            'item_type' => ['sometimes', Rule::enum(ItemType::class)],
            'tracking_type' => ['sometimes', Rule::enum(TrackingType::class)],
            'costing_method' => ['sometimes', Rule::enum(CostingMethod::class)],
            'item_category_id' => ['nullable', 'integer', 'min:1'],
            'item_brand_id' => ['nullable', 'integer', 'min:1'],
            'sku' => ['nullable', 'string', 'max:120'],
            'barcode' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'base_uom_id' => ['nullable', 'integer', 'min:1'],
            'standard_price' => ['nullable', 'decimal:0,6', 'gte:0'],
            'default_tax_group_id' => ['nullable', 'integer', 'min:1', $this->tenantExists('tax_groups', 'id')],
            'purchase_tax_group_id' => ['nullable', 'integer', 'min:1', $this->tenantExists('tax_groups', 'id')],
            'sales_tax_group_id' => ['nullable', 'integer', 'min:1', $this->tenantExists('tax_groups', 'id')],
            'is_stockable' => ['nullable', 'boolean'],
            'is_combo' => ['nullable', 'boolean'],
            'is_tax_exempt' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function toData(): UpdateItemData
    {
        return new UpdateItemData(
            code: $this->stringOrNull('code'),
            name: $this->stringOrNull('name'),
            itemType: $this->filled('item_type') ? ItemType::from((string) $this->input('item_type')) : null,
            organizationUnitId: null,
            itemCategoryId: $this->intOrNull('item_category_id'),
            itemBrandId: $this->intOrNull('item_brand_id'),
            sku: $this->stringOrNull('sku'),
            barcode: $this->stringOrNull('barcode'),
            description: $this->stringOrNull('description'),
            trackingType: $this->filled('tracking_type') ? TrackingType::from((string) $this->input('tracking_type')) : null,
            costingMethod: $this->filled('costing_method') ? CostingMethod::from((string) $this->input('costing_method')) : null,
            baseUomId: $this->intOrNull('base_uom_id'),
            standardPrice: $this->has('standard_price') ? $this->stringOrNull('standard_price') : null,
            defaultTaxGroupId: $this->intOrNull('default_tax_group_id'),
            purchaseTaxGroupId: $this->intOrNull('purchase_tax_group_id'),
            salesTaxGroupId: $this->intOrNull('sales_tax_group_id'),
            isStockable: $this->has('is_stockable') ? $this->boolean('is_stockable') : null,
            isCombo: $this->has('is_combo') ? $this->boolean('is_combo') : null,
            isTaxExempt: $this->has('is_tax_exempt') ? $this->boolean('is_tax_exempt') : null,
            isActive: $this->has('is_active') ? $this->boolean('is_active') : null,
            metadata: $this->has('metadata') ? $this->input('metadata') : null,
            provided: $this->providedEditableFields(),
        );
    }

    private function stringOrNull(string $key): ?string
    {
        return $this->filled($key) ? (string) $this->input($key) : null;
    }

    private function intOrNull(string $key): ?int
    {
        return $this->filled($key) ? (int) $this->input($key) : null;
    }

    /**
     * @return list<string>
     */
    private function providedEditableFields(): array
    {
        $input = $this->all();
        $editable = [
            'code',
            'name',
            'item_type',
            'tracking_type',
            'costing_method',
            'item_category_id',
            'item_brand_id',
            'sku',
            'barcode',
            'description',
            'base_uom_id',
            'standard_price',
            'default_tax_group_id',
            'purchase_tax_group_id',
            'sales_tax_group_id',
            'is_stockable',
            'is_combo',
            'is_tax_exempt',
            'is_active',
            'metadata',
        ];

        return array_values(array_filter(
            $editable,
            static fn (string $key): bool => array_key_exists($key, $input),
        ));
    }
}
