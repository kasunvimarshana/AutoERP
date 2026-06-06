<?php

declare(strict_types=1);

namespace Modules\Item\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Item\DTOs\CreateItemData;
use Modules\Item\DTOs\ItemBundleData;
use Modules\Item\DTOs\ItemCodeData;
use Modules\Item\DTOs\ItemPriceData;
use Modules\Item\DTOs\ItemUnitData;
use Modules\Item\DTOs\ItemUsageRuleData;
use Modules\Item\DTOs\ItemVariantData;
use Modules\Item\Enums\CostingMethod;
use Modules\Item\Enums\ItemCodeType;
use Modules\Item\Enums\ItemPriceType;
use Modules\Item\Enums\ItemType;
use Modules\Item\Enums\ItemUnitRole;
use Modules\Item\Enums\TrackingType;

final class StoreItemRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'code' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'item_type' => ['required', Rule::enum(ItemType::class)],
            'tracking_type' => ['nullable', Rule::enum(TrackingType::class)],
            'costing_method' => ['nullable', Rule::enum(CostingMethod::class)],
            'item_category_id' => ['nullable', 'integer', 'min:1'],
            'item_brand_id' => ['nullable', 'integer', 'min:1'],
            'sku' => ['nullable', 'string', 'max:100'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'base_uom_id' => ['nullable', 'integer', 'min:1'],
            'is_stockable' => ['nullable', 'boolean'],
            'is_combo' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
            'units' => ['nullable', 'array'],
            'units.*.uom_id' => ['required', 'integer', 'min:1'],
            'units.*.unit_role' => ['required', Rule::enum(ItemUnitRole::class)],
            'units.*.conversion_factor' => ['nullable', 'decimal:0,6', 'gt:0'],
            'variants' => ['nullable', 'array'],
            'variants.*.code' => ['required', 'string', 'max:100'],
            'variants.*.name' => ['required', 'string', 'max:255'],
            'bundles' => ['nullable', 'array'],
            'bundles.*.child_item_id' => ['required', 'integer', 'min:1'],
            'bundles.*.quantity' => ['required', 'decimal:0,6', 'gt:0'],
            'bundles.*.line_type' => ['required', Rule::in(['stock', 'service', 'labour', 'non_stock', 'charge'])],
            'prices' => ['nullable', 'array'],
            'prices.*.price_type' => ['required', Rule::enum(ItemPriceType::class)],
            'prices.*.amount' => ['required', 'decimal:0,6', 'min:0'],
            'codes' => ['nullable', 'array'],
            'codes.*.code_type' => ['required', Rule::enum(ItemCodeType::class)],
            'codes.*.code' => ['required', 'string', 'max:150'],
            'usage_rules' => ['nullable', 'array'],
            'usage_rules.*.module_code' => ['required', 'string', 'max:80'],
        ];
    }

    public function toData(): CreateItemData
    {
        return new CreateItemData(
            tenantId: $this->tenantId(),
            code: (string) $this->input('code'),
            name: (string) $this->input('name'),
            itemType: ItemType::from((string) $this->input('item_type')),
            organizationUnitId: $this->organizationUnitId(),
            itemCategoryId: $this->intOrNull('item_category_id'),
            itemBrandId: $this->intOrNull('item_brand_id'),
            sku: $this->stringOrNull('sku'),
            barcode: $this->stringOrNull('barcode'),
            description: $this->stringOrNull('description'),
            trackingType: TrackingType::from((string) $this->input('tracking_type', TrackingType::None->value)),
            costingMethod: CostingMethod::from((string) $this->input('costing_method', CostingMethod::None->value)),
            baseUomId: $this->intOrNull('base_uom_id'),
            isStockable: $this->boolean('is_stockable'),
            isCombo: $this->has('is_combo') ? $this->boolean('is_combo') : null,
            isActive: $this->boolean('is_active', true),
            metadata: $this->input('metadata'),
            units: array_map(static fn (array $row): ItemUnitData => new ItemUnitData(
                uomId: (int) $row['uom_id'],
                unitRole: ItemUnitRole::from((string) $row['unit_role']),
                conversionFactor: (string) ($row['conversion_factor'] ?? '1.000000'),
                isDefault: (bool) ($row['is_default'] ?? false),
                isActive: (bool) ($row['is_active'] ?? true),
            ), $this->input('units', [])),
            variants: array_map(static fn (array $row): ItemVariantData => new ItemVariantData(
                code: (string) $row['code'],
                name: (string) $row['name'],
                sku: $row['sku'] ?? null,
                barcode: $row['barcode'] ?? null,
                attributes: $row['attributes'] ?? null,
                isActive: (bool) ($row['is_active'] ?? true),
            ), $this->input('variants', [])),
            bundles: array_map(static fn (array $row): ItemBundleData => new ItemBundleData(
                childItemId: (int) $row['child_item_id'],
                quantity: (string) $row['quantity'],
                lineType: (string) $row['line_type'],
                childVariantId: isset($row['child_variant_id']) ? (int) $row['child_variant_id'] : null,
                uomId: isset($row['uom_id']) ? (int) $row['uom_id'] : null,
                isRequired: (bool) ($row['is_required'] ?? true),
                sortOrder: (int) ($row['sort_order'] ?? 0),
            ), $this->input('bundles', [])),
            prices: array_map(static fn (array $row): ItemPriceData => new ItemPriceData(
                priceType: ItemPriceType::from((string) $row['price_type']),
                amount: (string) $row['amount'],
                itemVariantId: isset($row['item_variant_id']) ? (int) $row['item_variant_id'] : null,
                currencyId: isset($row['currency_id']) ? (int) $row['currency_id'] : null,
                uomId: isset($row['uom_id']) ? (int) $row['uom_id'] : null,
                effectiveFrom: $row['effective_from'] ?? null,
                effectiveTo: $row['effective_to'] ?? null,
                isActive: (bool) ($row['is_active'] ?? true),
            ), $this->input('prices', [])),
            codes: array_map(static fn (array $row): ItemCodeData => new ItemCodeData(
                codeType: ItemCodeType::from((string) $row['code_type']),
                code: (string) $row['code'],
                itemVariantId: isset($row['item_variant_id']) ? (int) $row['item_variant_id'] : null,
                partyType: $row['party_type'] ?? null,
                partyId: isset($row['party_id']) ? (int) $row['party_id'] : null,
                isPrimary: (bool) ($row['is_primary'] ?? false),
            ), $this->input('codes', [])),
            usageRules: array_map(static fn (array $row): ItemUsageRuleData => new ItemUsageRuleData(
                moduleCode: (string) $row['module_code'],
                isEnabled: (bool) ($row['is_enabled'] ?? true),
            ), $this->input('usage_rules', [])),
        );
    }

    private function intOrNull(string $key): ?int
    {
        return $this->filled($key) ? (int) $this->input($key) : null;
    }

    private function stringOrNull(string $key): ?string
    {
        return $this->filled($key) ? (string) $this->input($key) : null;
    }
}
