<?php

declare(strict_types=1);

namespace Modules\Supplier\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Supplier\DTOs\SupplierItemMappingData;
use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Models\SupplierItemMapping;
use Modules\Supplier\Validators\SupplierValidationService;

final class SupplierItemMappingService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly SupplierValidationService $validator,
    ) {}

    public function create(Supplier $supplier, SupplierItemMappingData $data): SupplierItemMapping
    {
        $this->validator->validateItemMapping($supplier, $data);

        if ($supplier->itemMappings()->withTrashed()
            ->where('item_id', $data->itemId)
            ->where('item_variant_id', $data->itemVariantId)
            ->exists()) {
            throw new InvalidArgumentException('Supplier item mapping already exists.');
        }

        if ($data->isPreferred) {
            $this->clearPreferredForItem($supplier, $data);
        }

        return $supplier->itemMappings()->create([
            'tenant_id' => $supplier->tenant_id,
            'organization_unit_id' => $supplier->organization_unit_id,
            'item_id' => $data->itemId,
            'item_variant_id' => $data->itemVariantId,
            'supplier_item_code' => $data->supplierItemCode,
            'supplier_item_name' => $data->supplierItemName,
            'default_purchase_uom_id' => $data->defaultPurchaseUomId,
            'minimum_order_quantity' => $this->math->normalize($data->minimumOrderQuantity),
            'lead_time_days' => $data->leadTimeDays,
            'is_preferred' => $data->isPreferred,
            'is_active' => $data->isActive,
        ]);
    }

    public function update(
        Supplier $supplier,
        SupplierItemMapping $mapping,
        SupplierItemMappingData $data,
    ): SupplierItemMapping {
        $this->assertOwned($supplier, $mapping);
        $this->validator->validateItemMapping($supplier, $data);

        $duplicate = $supplier->itemMappings()->withTrashed()
            ->whereKeyNot($mapping->getKey())
            ->where('item_id', $data->itemId);
        $data->itemVariantId === null
            ? $duplicate->whereNull('item_variant_id')
            : $duplicate->where('item_variant_id', $data->itemVariantId);
        if ($duplicate->exists()) {
            throw new InvalidArgumentException('Supplier item mapping already exists.');
        }
        if ($data->isPreferred) {
            $this->clearPreferredForItem($supplier, $data);
        }

        $mapping->fill([
            'item_id' => $data->itemId,
            'item_variant_id' => $data->itemVariantId,
            'supplier_item_code' => $data->supplierItemCode,
            'supplier_item_name' => $data->supplierItemName,
            'default_purchase_uom_id' => $data->defaultPurchaseUomId,
            'minimum_order_quantity' => $this->math->normalize($data->minimumOrderQuantity),
            'lead_time_days' => $data->leadTimeDays,
            'is_preferred' => $data->isPreferred,
            'is_active' => $data->isActive,
        ])->save();

        return $mapping->refresh()->load(['item.category', 'item.brand', 'variant', 'defaultPurchaseUom']);
    }

    public function delete(Supplier $supplier, SupplierItemMapping $mapping): void
    {
        $this->assertOwned($supplier, $mapping);
        $mapping->delete();
    }

    /**
     * @param  list<SupplierItemMappingData>  $mappings
     */
    public function replace(Supplier $supplier, array $mappings): void
    {
        $supplier->itemMappings()->delete();
        foreach ($mappings as $mapping) {
            $this->create($supplier, $mapping);
        }
    }

    private function clearPreferredForItem(Supplier $supplier, SupplierItemMappingData $data): void
    {
        $query = SupplierItemMapping::query()
            ->where('tenant_id', $supplier->tenant_id)
            ->where('item_id', $data->itemId);

        $data->itemVariantId === null
            ? $query->whereNull('item_variant_id')
            : $query->where('item_variant_id', $data->itemVariantId);

        $query->update(['is_preferred' => false]);
    }

    private function assertOwned(Supplier $supplier, SupplierItemMapping $mapping): void
    {
        if ((int) $mapping->supplier_id !== (int) $supplier->getKey()) {
            throw new InvalidArgumentException('Supplier item mapping does not belong to the supplier.');
        }
    }
}
