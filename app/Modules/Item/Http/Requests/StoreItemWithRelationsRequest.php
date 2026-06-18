<?php

declare(strict_types=1);

namespace Modules\Item\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Item\DTOs\CreateItemData;
use Modules\Item\Enums\CostingMethod;
use Modules\Item\Enums\ItemCodeType;
use Modules\Item\Enums\ItemPriceType;
use Modules\Item\Enums\ItemType;
use Modules\Item\Enums\ItemUnitRole;
use Modules\Item\Enums\TrackingType;
use Modules\Item\Http\Requests\Concerns\MapsItemData;

final class StoreItemWithRelationsRequest extends TenantScopedRequest
{
    use MapsItemData;

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'item' => ['required', 'array'],
            'item.code' => ['required', 'string', 'max:80'],
            'item.name' => ['required', 'string', 'max:255'],
            'item.item_type' => ['required', Rule::enum(ItemType::class)],
            'item.tracking_type' => ['nullable', Rule::enum(TrackingType::class)],
            'item.costing_method' => ['nullable', Rule::enum(CostingMethod::class)],
            'item.item_category_id' => ['nullable', 'integer', 'min:1'],
            'item.item_brand_id' => ['nullable', 'integer', 'min:1'],
            'item.base_uom_id' => ['nullable', 'integer', 'min:1'],
            'item.default_tax_group_id' => ['nullable', 'integer', 'min:1', 'exists:tax_groups,id'],
            'item.purchase_tax_group_id' => ['nullable', 'integer', 'min:1', 'exists:tax_groups,id'],
            'item.sales_tax_group_id' => ['nullable', 'integer', 'min:1', 'exists:tax_groups,id'],
            'item.sku' => ['nullable', 'string', 'max:120'],
            'item.barcode' => ['nullable', 'string', 'max:120'],
            'item.description' => ['nullable', 'string'],
            'item.is_stockable' => ['nullable', 'boolean'],
            'item.is_combo' => ['nullable', 'boolean'],
            'item.is_tax_exempt' => ['nullable', 'boolean'],
            'item.is_active' => ['nullable', 'boolean'],
            'item.metadata' => ['nullable', 'array'],
            'item.standard_price' => ['nullable', 'decimal:0,6', 'gte:0'],
            'units' => ['nullable', 'array'],
            'units.*.uom_id' => ['required', 'integer', 'min:1'],
            'units.*.unit_role' => ['required', Rule::enum(ItemUnitRole::class)],
            'units.*.conversion_factor' => ['nullable', 'decimal:0,6', 'gt:0'],
            'units.*.is_default' => ['nullable', 'boolean'],
            'units.*.is_active' => ['nullable', 'boolean'],
            'variants' => ['nullable', 'array'],
            'variants.*.code' => ['required', 'string', 'max:80'],
            'variants.*.name' => ['required', 'string', 'max:255'],
            'variants.*.sku' => ['nullable', 'string', 'max:120'],
            'variants.*.barcode' => ['nullable', 'string', 'max:120'],
            'variants.*.attributes' => ['nullable', 'array'],
            'variants.*.is_active' => ['nullable', 'boolean'],
            'bundles' => ['nullable', 'array'],
            'bundles.*.child_item_id' => ['required', 'integer', 'min:1'],
            'bundles.*.child_variant_id' => ['nullable', 'integer', 'min:1'],
            'bundles.*.quantity' => ['required', 'decimal:0,6', 'gt:0'],
            'bundles.*.uom_id' => ['nullable', 'integer', 'min:1'],
            'bundles.*.line_type' => ['required', Rule::in(['stock', 'service', 'labour', 'non_stock', 'charge'])],
            'bundles.*.is_required' => ['nullable', 'boolean'],
            'bundles.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'prices' => ['nullable', 'array'],
            'prices.*.price_type' => ['required', Rule::enum(ItemPriceType::class)],
            'prices.*.amount' => ['required', 'decimal:0,6', 'gte:0'],
            'prices.*.item_variant_id' => ['nullable', 'integer', 'min:1'],
            'prices.*.currency_id' => ['nullable', 'integer', 'min:1'],
            'prices.*.uom_id' => ['nullable', 'integer', 'min:1'],
            'prices.*.effective_from' => ['nullable', 'date'],
            'prices.*.effective_to' => ['nullable', 'date'],
            'prices.*.is_active' => ['nullable', 'boolean'],
            'codes' => ['nullable', 'array'],
            'codes.*.code_type' => ['required', Rule::enum(ItemCodeType::class)],
            'codes.*.code' => ['required', 'string', 'max:120'],
            'codes.*.item_variant_id' => ['nullable', 'integer', 'min:1'],
            'codes.*.party_type' => ['nullable', 'string', 'max:255'],
            'codes.*.party_id' => ['nullable', 'integer', 'min:1'],
            'codes.*.is_primary' => ['nullable', 'boolean'],
            'usage_rules' => ['nullable', 'array'],
            'usage_rules.*.module_code' => ['required', 'string', 'max:50', 'regex:/^[a-z][a-z0-9_-]*$/'],
            'usage_rules.*.is_enabled' => ['nullable', 'boolean'],
        ];
    }

    public function toData(): CreateItemData
    {
        $validated = $this->validated();

        return $this->mapItemData((array) $validated['item'], $validated);
    }
}
