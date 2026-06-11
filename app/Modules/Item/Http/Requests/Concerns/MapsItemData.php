<?php

declare(strict_types=1);

namespace Modules\Item\Http\Requests\Concerns;

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

trait MapsItemData
{
    private function mapItemData(array $item, array $relations = []): CreateItemData
    {
        return new CreateItemData(
            tenantId: $this->tenantId(),
            code: (string) $item['code'],
            name: (string) $item['name'],
            itemType: ItemType::from((string) $item['item_type']),
            organizationUnitId: $this->organizationUnitId(),
            itemCategoryId: $this->nullableInt($item, 'item_category_id'),
            itemBrandId: $this->nullableInt($item, 'item_brand_id'),
            sku: $this->nullableString($item, 'sku'),
            barcode: $this->nullableString($item, 'barcode'),
            description: $this->nullableString($item, 'description'),
            trackingType: TrackingType::from((string) ($item['tracking_type'] ?? TrackingType::None->value)),
            costingMethod: CostingMethod::from((string) ($item['costing_method'] ?? CostingMethod::None->value)),
            baseUomId: $this->nullableInt($item, 'base_uom_id'),
            defaultTaxGroupId: $this->nullableInt($item, 'default_tax_group_id'),
            purchaseTaxGroupId: $this->nullableInt($item, 'purchase_tax_group_id'),
            salesTaxGroupId: $this->nullableInt($item, 'sales_tax_group_id'),
            isStockable: (bool) ($item['is_stockable'] ?? false),
            isCombo: array_key_exists('is_combo', $item) ? (bool) $item['is_combo'] : null,
            isTaxExempt: (bool) ($item['is_tax_exempt'] ?? false),
            isActive: (bool) ($item['is_active'] ?? true),
            metadata: $item['metadata'] ?? null,
            units: array_map(static fn (array $row): ItemUnitData => new ItemUnitData(
                uomId: (int) $row['uom_id'],
                unitRole: ItemUnitRole::from((string) $row['unit_role']),
                conversionFactor: (string) ($row['conversion_factor'] ?? '1'),
                isDefault: (bool) ($row['is_default'] ?? false),
                isActive: (bool) ($row['is_active'] ?? true),
            ), $relations['units'] ?? []),
            variants: array_map(static fn (array $row): ItemVariantData => new ItemVariantData(
                code: (string) $row['code'],
                name: (string) $row['name'],
                sku: $row['sku'] ?? null,
                barcode: $row['barcode'] ?? null,
                attributes: $row['attributes'] ?? null,
                isActive: (bool) ($row['is_active'] ?? true),
            ), $relations['variants'] ?? []),
            bundles: array_map(static fn (array $row): ItemBundleData => new ItemBundleData(
                childItemId: (int) $row['child_item_id'],
                quantity: (string) $row['quantity'],
                lineType: (string) $row['line_type'],
                childVariantId: isset($row['child_variant_id']) ? (int) $row['child_variant_id'] : null,
                uomId: isset($row['uom_id']) ? (int) $row['uom_id'] : null,
                isRequired: (bool) ($row['is_required'] ?? true),
                sortOrder: (int) ($row['sort_order'] ?? 0),
            ), $relations['bundles'] ?? []),
            prices: array_map(static fn (array $row): ItemPriceData => new ItemPriceData(
                priceType: ItemPriceType::from((string) $row['price_type']),
                amount: (string) $row['amount'],
                itemVariantId: isset($row['item_variant_id']) ? (int) $row['item_variant_id'] : null,
                currencyId: isset($row['currency_id']) ? (int) $row['currency_id'] : null,
                uomId: isset($row['uom_id']) ? (int) $row['uom_id'] : null,
                effectiveFrom: $row['effective_from'] ?? null,
                effectiveTo: $row['effective_to'] ?? null,
                isActive: (bool) ($row['is_active'] ?? true),
            ), $relations['prices'] ?? []),
            codes: array_map(static fn (array $row): ItemCodeData => new ItemCodeData(
                codeType: ItemCodeType::from((string) $row['code_type']),
                code: (string) $row['code'],
                itemVariantId: isset($row['item_variant_id']) ? (int) $row['item_variant_id'] : null,
                partyType: $row['party_type'] ?? null,
                partyId: isset($row['party_id']) ? (int) $row['party_id'] : null,
                isPrimary: (bool) ($row['is_primary'] ?? false),
            ), $relations['codes'] ?? []),
            usageRules: array_map(static fn (array $row): ItemUsageRuleData => new ItemUsageRuleData(
                moduleCode: (string) $row['module_code'],
                isEnabled: (bool) ($row['is_enabled'] ?? true),
            ), $relations['usage_rules'] ?? []),
        );
    }

    private function nullableInt(array $data, string $key): ?int
    {
        return isset($data[$key]) && $data[$key] !== '' ? (int) $data[$key] : null;
    }

    private function nullableString(array $data, string $key): ?string
    {
        return isset($data[$key]) && trim((string) $data[$key]) !== '' ? (string) $data[$key] : null;
    }
}
