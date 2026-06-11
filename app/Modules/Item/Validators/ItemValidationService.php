<?php

declare(strict_types=1);

namespace Modules\Item\Validators;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Item\DTOs\CreateItemData;
use Modules\Item\DTOs\ItemBundleData;
use Modules\Item\DTOs\ItemCodeData;
use Modules\Item\DTOs\ItemPriceData;
use Modules\Item\DTOs\ItemUnitData;
use Modules\Item\DTOs\ItemUsageRuleData;
use Modules\Item\DTOs\ItemVariantData;
use Modules\Item\DTOs\UpdateItemData;
use Modules\Item\Enums\ItemType;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemBrand;
use Modules\Item\Models\ItemBundle;
use Modules\Item\Models\ItemCategory;
use Modules\Item\Models\ItemVariant;
use Modules\UOM\Models\UnitOfMeasureModel;

final class ItemValidationService
{
    private const BUNDLE_PARENT_TYPES = [
        ItemType::Combo->value,
        ItemType::Package->value,
    ];

    private const BUNDLE_CHILD_TYPES = [
        ItemType::Stock->value,
        ItemType::Service->value,
        ItemType::Labour->value,
        ItemType::NonStock->value,
        ItemType::Combo->value,
        ItemType::Package->value,
    ];

    private const BUNDLE_LINE_TYPES = [
        ItemType::Stock->value,
        ItemType::Service->value,
        ItemType::Labour->value,
        ItemType::NonStock->value,
        'charge',
    ];

    public function __construct(
        private readonly DecimalMath $math,
        private readonly \Modules\Item\Services\ItemBaseUomUsageAuditService $baseUomUsageAudit,
    ) {}

    public function validateCreate(CreateItemData $data): void
    {
        $this->assertText($data->code, 'Item code is required.');
        $this->assertText($data->name, 'Item name is required.');
        $this->assertCodeIsUnique($data->tenantId, $data->code);
        $this->assertSkuIsUnique($data->tenantId, $data->sku);
        $this->assertBarcodeIsUnique($data->tenantId, $data->barcode);
        $this->assertCategoryIsUsable($data->tenantId, $data->organizationUnitId, $data->itemCategoryId);
        $this->assertBrandIsUsable($data->tenantId, $data->organizationUnitId, $data->itemBrandId);
        $this->assertUomIsUsable($data->tenantId, $data->organizationUnitId, $data->baseUomId);
        $this->assertTaxGroupIsUsable($data->tenantId, $data->organizationUnitId, $data->defaultTaxGroupId);
        $this->assertTaxGroupIsUsable($data->tenantId, $data->organizationUnitId, $data->purchaseTaxGroupId);
        $this->assertTaxGroupIsUsable($data->tenantId, $data->organizationUnitId, $data->salesTaxGroupId);
        $this->assertTypeRules($data->itemType, $data->isStockable);
    }

    public function validateUpdate(Item $item, UpdateItemData $data): void
    {
        $tenantId = (int) $item->tenant_id;
        $organizationUnitId = $data->organizationUnitId ?? $item->organization_unit_id;

        if (in_array('base_uom_id', $data->provided, true)
            && (int) ($data->baseUomId ?? 0) !== (int) ($item->base_uom_id ?? 0)
            && $this->baseUomUsageAudit->audit($item)['has_usage']) {
            throw new InvalidArgumentException('Base UOM cannot be edited directly after item usage. Use the Base UOM Conversion Wizard.');
        }

        if ($data->code !== null) {
            $this->assertText($data->code, 'Item code is required.');
            $this->assertCodeIsUnique($tenantId, $data->code, (int) $item->getKey());
        }

        if ($data->name !== null) {
            $this->assertText($data->name, 'Item name is required.');
        }

        $this->assertSkuIsUnique($tenantId, $data->sku, (int) $item->getKey());
        $this->assertBarcodeIsUnique($tenantId, $data->barcode, (int) $item->getKey());
        $this->assertCategoryIsUsable($tenantId, $organizationUnitId, $data->itemCategoryId);
        $this->assertBrandIsUsable($tenantId, $organizationUnitId, $data->itemBrandId);
        $this->assertUomIsUsable($tenantId, $organizationUnitId, $data->baseUomId);
        $this->assertTaxGroupIsUsable($tenantId, $organizationUnitId, $data->defaultTaxGroupId);
        $this->assertTaxGroupIsUsable($tenantId, $organizationUnitId, $data->purchaseTaxGroupId);
        $this->assertTaxGroupIsUsable($tenantId, $organizationUnitId, $data->salesTaxGroupId);

        $itemType = $data->itemType ?? $item->item_type;
        $isStockable = $data->isStockable ?? (bool) $item->is_stockable;
        $this->assertTypeRules(
            $itemType instanceof ItemType ? $itemType : ItemType::from((string) $itemType),
            $isStockable,
        );
    }

    public function validateUnit(Item $item, ItemUnitData $data, ?int $ignoreUnitId = null): void
    {
        $this->assertPositiveDecimal($data->conversionFactor, 'Item unit conversion factor must be greater than zero.');
        $this->assertUomIsUsable((int) $item->tenant_id, $item->organization_unit_id, $data->uomId);

        $duplicate = $item->units()
            ->where('uom_id', $data->uomId)
            ->where('unit_role', $data->unitRole->value);
        if ($ignoreUnitId !== null) {
            $duplicate->whereKeyNot($ignoreUnitId);
        }
        if ($duplicate->exists()) {
            throw new InvalidArgumentException('This UOM is already assigned for the selected item unit role.');
        }

        if ($data->unitRole->value === 'base') {
            if ($item->base_uom_id === null || (int) $item->base_uom_id !== $data->uomId) {
                throw new InvalidArgumentException('Base item unit must match the item base UOM.');
            }
            if ($this->math->compare($data->conversionFactor, '1') !== 0) {
                throw new InvalidArgumentException('Base item unit conversion factor must be 1.');
            }
        }
    }

    public function validateVariant(Item $item, ItemVariantData $data, ?int $ignoreVariantId = null): void
    {
        $this->assertText($data->code, 'Item variant code is required.');
        $this->assertText($data->name, 'Item variant name is required.');
        $this->assertVariantCodeIsUnique((int) $item->tenant_id, $data->code, $ignoreVariantId);
        $this->assertVariantSkuIsUnique((int) $item->tenant_id, $data->sku, $ignoreVariantId);
        $this->assertVariantBarcodeIsUnique((int) $item->tenant_id, $data->barcode, $ignoreVariantId);
    }

    public function validateBundle(Item $parent, ItemBundleData $data): void
    {
        $this->assertPositiveDecimal($data->quantity, 'Item bundle quantity must be greater than zero.');

        $parentType = $parent->item_type instanceof ItemType ? $parent->item_type->value : (string) $parent->item_type;
        if (! in_array($parentType, self::BUNDLE_PARENT_TYPES, true)) {
            throw new InvalidArgumentException('Only combo or package items can own bundle lines.');
        }

        if ((int) $parent->getKey() === $data->childItemId) {
            throw new InvalidArgumentException('Item bundle cannot reference itself.');
        }

        if (! in_array($data->lineType, self::BUNDLE_LINE_TYPES, true)) {
            throw new InvalidArgumentException('Item bundle line type is invalid.');
        }

        $child = Item::query()->findOrFail($data->childItemId);
        $this->assertItemScope($parent, $child);

        if ($this->wouldCreateCycle((int) $parent->getKey(), $data->childItemId)) {
            throw new InvalidArgumentException('Item bundle cannot create a circular composition.');
        }

        $childType = $child->item_type instanceof ItemType ? $child->item_type->value : (string) $child->item_type;
        if (! in_array($childType, self::BUNDLE_CHILD_TYPES, true)) {
            throw new InvalidArgumentException('Item bundle child type is not supported.');
        }

        if ($data->childVariantId !== null) {
            $variant = ItemVariant::query()->findOrFail($data->childVariantId);
            if ((int) $variant->item_id !== $data->childItemId) {
                throw new InvalidArgumentException('Item bundle variant must belong to the child item.');
            }
        }

        $this->assertUomIsUsable((int) $parent->tenant_id, $parent->organization_unit_id, $data->uomId);
    }

    public function validatePrice(Item $item, ItemPriceData $data): void
    {
        $this->assertNotNegativeDecimal($data->amount, 'Item price amount cannot be negative.');
        $this->assertVariantBelongsToItem($item, $data->itemVariantId);
        $this->assertUomIsUsable((int) $item->tenant_id, $item->organization_unit_id, $data->uomId);

        if ($data->effectiveFrom !== null && $data->effectiveTo !== null && $data->effectiveFrom > $data->effectiveTo) {
            throw new InvalidArgumentException('Item price effective from date cannot be after effective to date.');
        }
    }

    public function validateCode(Item $item, ItemCodeData $data): void
    {
        $this->assertText($data->code, 'Item alternative code is required.');
        $this->assertVariantBelongsToItem($item, $data->itemVariantId);
    }

    public function validateUsageRule(Item $item, ItemUsageRuleData $data): void
    {
        $this->assertText($data->moduleCode, 'Item usage rule module code is required.');
        if (! preg_match('/^[a-z][a-z0-9_]*$/', $data->moduleCode)) {
            throw new InvalidArgumentException('Item usage rule module code must be lowercase snake case.');
        }
    }

    private function assertTypeRules(ItemType $itemType, bool $isStockable): void
    {
        if (in_array($itemType, [ItemType::Service, ItemType::Labour], true) && $isStockable) {
            throw new InvalidArgumentException('Service and labour items cannot be stockable.');
        }

    }

    private function assertCodeIsUnique(int $tenantId, string $code, ?int $ignoreItemId = null): void
    {
        $query = Item::query()->where('tenant_id', $tenantId)->where('code', $code);
        $this->ignoreKey($query, $ignoreItemId);

        if ($query->exists()) {
            throw new InvalidArgumentException('Item code already exists for this tenant.');
        }
    }

    private function assertSkuIsUnique(int $tenantId, ?string $sku, ?int $ignoreItemId = null): void
    {
        if ($sku === null || trim($sku) === '') {
            return;
        }

        $query = Item::query()->where('tenant_id', $tenantId)->where('sku', $sku);
        $this->ignoreKey($query, $ignoreItemId);

        if ($query->exists()) {
            throw new InvalidArgumentException('Item SKU already exists for this tenant.');
        }
    }

    private function assertBarcodeIsUnique(int $tenantId, ?string $barcode, ?int $ignoreItemId = null): void
    {
        if ($barcode === null || trim($barcode) === '') {
            return;
        }

        $query = Item::query()->where('tenant_id', $tenantId)->where('barcode', $barcode);
        $this->ignoreKey($query, $ignoreItemId);

        if ($query->exists()) {
            throw new InvalidArgumentException('Item barcode already exists for this tenant.');
        }
    }

    private function assertVariantCodeIsUnique(int $tenantId, string $code, ?int $ignoreVariantId = null): void
    {
        $query = ItemVariant::query()->where('tenant_id', $tenantId)->where('code', $code);
        $this->ignoreKey($query, $ignoreVariantId);

        if ($query->exists()) {
            throw new InvalidArgumentException('Item variant code already exists for this tenant.');
        }
    }

    private function assertVariantSkuIsUnique(int $tenantId, ?string $sku, ?int $ignoreVariantId = null): void
    {
        if ($sku === null || trim($sku) === '') {
            return;
        }

        $query = ItemVariant::query()->where('tenant_id', $tenantId)->where('sku', $sku);
        $this->ignoreKey($query, $ignoreVariantId);

        if ($query->exists()) {
            throw new InvalidArgumentException('Item variant SKU already exists for this tenant.');
        }
    }

    private function assertVariantBarcodeIsUnique(int $tenantId, ?string $barcode, ?int $ignoreVariantId = null): void
    {
        if ($barcode === null || trim($barcode) === '') {
            return;
        }

        $query = ItemVariant::query()->where('tenant_id', $tenantId)->where('barcode', $barcode);
        $this->ignoreKey($query, $ignoreVariantId);

        if ($query->exists()) {
            throw new InvalidArgumentException('Item variant barcode already exists for this tenant.');
        }
    }

    private function assertCategoryIsUsable(int $tenantId, ?int $organizationUnitId, ?int $categoryId): void
    {
        if ($categoryId === null) {
            return;
        }

        $category = ItemCategory::query()->findOrFail($categoryId);
        $this->assertScopedRecord($tenantId, $organizationUnitId, (int) $category->tenant_id, $category->organization_unit_id);
        if (! (bool) $category->is_active) {
            throw new InvalidArgumentException('Inactive item category cannot be used.');
        }
    }

    private function assertBrandIsUsable(int $tenantId, ?int $organizationUnitId, ?int $brandId): void
    {
        if ($brandId === null) {
            return;
        }

        $brand = ItemBrand::query()->findOrFail($brandId);
        $this->assertScopedRecord($tenantId, $organizationUnitId, (int) $brand->tenant_id, $brand->organization_unit_id);
        if (! (bool) $brand->is_active) {
            throw new InvalidArgumentException('Inactive item brand cannot be used.');
        }
    }

    private function assertUomIsUsable(int $tenantId, ?int $organizationUnitId, ?int $uomId): void
    {
        if ($uomId === null) {
            return;
        }

        $uom = UnitOfMeasureModel::query()->findOrFail($uomId);
        $this->assertScopedRecord($tenantId, $organizationUnitId, (int) $uom->tenant_id, $uom->organization_unit_id);
        if (! (bool) $uom->is_active) {
            throw new InvalidArgumentException('Inactive UOM cannot be used for item master data.');
        }
    }

    private function assertTaxGroupIsUsable(int $tenantId, ?int $organizationUnitId, ?int $taxGroupId): void
    {
        if ($taxGroupId === null) {
            return;
        }

        $group = \Modules\Tax\Models\TaxGroup::query()->findOrFail($taxGroupId);
        $this->assertScopedRecord($tenantId, $organizationUnitId, (int) $group->tenant_id, $group->organization_unit_id);
        if (! (bool) $group->active) {
            throw new InvalidArgumentException('Inactive tax group cannot be used for item master data.');
        }
    }

    private function assertScopedRecord(int $tenantId, ?int $organizationUnitId, int $recordTenantId, ?int $recordOrganizationUnitId): void
    {
        if ($recordTenantId !== $tenantId) {
            throw new InvalidArgumentException('Item reference belongs to a different tenant.');
        }

        if ($recordOrganizationUnitId !== null && $organizationUnitId !== null && (int) $recordOrganizationUnitId !== $organizationUnitId) {
            throw new InvalidArgumentException('Item reference belongs to a different organization unit.');
        }
    }

    private function assertItemScope(Item $parent, Item $child): void
    {
        $this->assertScopedRecord(
            (int) $parent->tenant_id,
            $parent->organization_unit_id,
            (int) $child->tenant_id,
            $child->organization_unit_id,
        );
    }

    private function assertVariantBelongsToItem(Item $item, ?int $variantId): void
    {
        if ($variantId === null) {
            return;
        }

        $variant = ItemVariant::query()->findOrFail($variantId);
        if ((int) $variant->item_id !== (int) $item->getKey()) {
            throw new InvalidArgumentException('Item variant must belong to the item.');
        }
    }

    private function assertPositiveDecimal(string $value, string $message): void
    {
        if ($this->math->isNegative($value) || $this->math->isZero($value)) {
            throw new InvalidArgumentException($message);
        }
    }

    private function assertNotNegativeDecimal(string $value, string $message): void
    {
        if ($this->math->isNegative($value)) {
            throw new InvalidArgumentException($message);
        }
    }

    private function assertText(string $value, string $message): void
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException($message);
        }
    }

    private function ignoreKey(Builder $query, ?int $id): void
    {
        if ($id !== null) {
            $query->whereKeyNot($id);
        }
    }

    private function wouldCreateCycle(int $parentItemId, int $childItemId): bool
    {
        $visited = [];
        $stack = [$childItemId];

        while ($stack !== []) {
            $current = array_pop($stack);
            if ($current === $parentItemId) {
                return true;
            }

            if (isset($visited[$current])) {
                continue;
            }
            $visited[$current] = true;

            $children = ItemBundle::query()
                ->where('parent_item_id', $current)
                ->pluck('child_item_id')
                ->all();

            foreach ($children as $child) {
                $stack[] = (int) $child;
            }
        }

        return false;
    }
}
