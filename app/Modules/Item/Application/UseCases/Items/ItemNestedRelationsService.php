<?php

declare(strict_types=1);

namespace Modules\Item\Application\UseCases\Items;

use InvalidArgumentException;
use Modules\Item\Application\Repositories\ComboItemRepositoryInterface;
use Modules\Item\Application\Repositories\ItemAttributeRepositoryInterface;
use Modules\Item\Application\Repositories\ItemAttributeValueRepositoryInterface;
use Modules\Item\Application\Repositories\ItemRepositoryInterface;
use Modules\Item\Application\Repositories\ItemVariantAttributeRepositoryInterface;
use Modules\Item\Application\Repositories\ItemVariantAttributeValueRepositoryInterface;
use Modules\Item\Application\Repositories\ItemVariantRepositoryInterface;
use Modules\UOM\Application\Repositories\UomConversionRepositoryInterface;

final class ItemNestedRelationsService
{
    public function __construct(
        private readonly ItemRepositoryInterface $itemRepository,
        private readonly ItemAttributeRepositoryInterface $attributeRepository,
        private readonly ItemAttributeValueRepositoryInterface $attributeValueRepository,
        private readonly ItemVariantRepositoryInterface $variantRepository,
        private readonly ItemVariantAttributeRepositoryInterface $variantAttributeRepository,
        private readonly ItemVariantAttributeValueRepositoryInterface $variantAttributeValueRepository,
        private readonly ComboItemRepositoryInterface $comboItemRepository,
        private readonly UomConversionRepositoryInterface $uomConversionRepository,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $nested
     */
    public function syncForItem(
        int $tenantId,
        int $itemId,
        array $payload,
        array $nested,
        bool $replaceExisting,
    ): void {
        $this->assertUnitConvertibility($tenantId, $itemId, $payload);

        if (isset($nested['attributes']) && is_array($nested['attributes'])) {
            $this->syncAttributes($tenantId, $itemId, $nested['attributes'], $payload, $replaceExisting);
        }

        if (isset($nested['variants']) && is_array($nested['variants'])) {
            $this->syncVariants($tenantId, $itemId, $nested['variants'], $payload, $replaceExisting);
        }

        if (isset($nested['combo_items']) && is_array($nested['combo_items'])) {
            $this->syncComboItems($tenantId, $itemId, $nested['combo_items'], $payload, $replaceExisting);
        }

        if (isset($nested['uom_conversions']) && is_array($nested['uom_conversions'])) {
            $this->syncUomConversions($tenantId, $itemId, $nested['uom_conversions'], $payload, $replaceExisting);
        }

        if (isset($nested['metadata_values']) && is_array($nested['metadata_values'])) {
            $this->itemRepository->syncMetadataValues($tenantId, $itemId, $nested['metadata_values']);
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function assertUnitConvertibility(int $tenantId, int $itemId, array $payload): void
    {
        $baseUomId = isset($payload['base_uom_id']) ? (int) $payload['base_uom_id'] : null;
        if ($baseUomId === null || $baseUomId <= 0) {
            return;
        }

        foreach (['purchase_uom_id', 'sales_uom_id', 'service_uom_id', 'rental_uom_id'] as $field) {
            if (! array_key_exists($field, $payload) || $payload[$field] === null || $payload[$field] === '') {
                continue;
            }

            $targetUomId = (int) $payload[$field];
            if ($targetUomId === $baseUomId) {
                continue;
            }

            if (! $this->isConvertible($tenantId, $itemId, $baseUomId, $targetUomId)) {
                throw new InvalidArgumentException(sprintf(
                    'The selected %s (%d) is not convertible from base_uom_id (%d).',
                    $field,
                    $targetUomId,
                    $baseUomId,
                ));
            }
        }
    }

    private function isConvertible(int $tenantId, int $itemId, int $fromUomId, int $toUomId): bool
    {
        foreach ([null, $itemId] as $conversionItemId) {
            $directCriteria = [
                'tenant_id' => $tenantId,
                'item_id' => $conversionItemId,
                'from_uom_id' => $fromUomId,
                'to_uom_id' => $toUomId,
                'is_active' => true,
            ];

            if ($this->uomConversionRepository->exists($directCriteria)) {
                return true;
            }

            $reverseCriteria = [
                'tenant_id' => $tenantId,
                'item_id' => $conversionItemId,
                'from_uom_id' => $toUomId,
                'to_uom_id' => $fromUomId,
                'is_active' => true,
                'is_bidirectional' => true,
            ];

            if ($this->uomConversionRepository->exists($reverseCriteria)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<array<string, mixed>> $attributes
     * @param array<string, mixed> $payload
     */
    private function syncAttributes(
        int $tenantId,
        int $itemId,
        array $attributes,
        array $payload,
        bool $replaceExisting,
    ): void {
        if ($replaceExisting) {
            $existingMappings = $this->variantAttributeRepository->list([
                'tenant_id' => $tenantId,
                'item_id' => $itemId,
            ]);

            foreach ($existingMappings as $mapping) {
                $this->variantAttributeRepository->delete((int) $mapping->id());
            }
        }

        foreach ($attributes as $attributeInput) {
            $attributeId = isset($attributeInput['id']) ? (int) $attributeInput['id'] : null;
            if ($attributeId === null || $attributeId <= 0) {
                $attribute = $this->attributeRepository->create([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $payload['organization_unit_id'] ?? null,
                    'group_id' => $attributeInput['group_id'] ?? null,
                    'name' => (string) ($attributeInput['name'] ?? ''),
                    'type' => $attributeInput['type'] ?? null,
                    'is_required' => (bool) ($attributeInput['is_required'] ?? false),
                    'metadata' => is_array($attributeInput['metadata'] ?? null)
                        ? $attributeInput['metadata']
                        : null,
                ]);
                $attributeId = (int) $attribute->id();
            }

            if (isset($attributeInput['values']) && is_array($attributeInput['values'])) {
                foreach ($attributeInput['values'] as $valueInput) {
                    if (! isset($valueInput['value'])) {
                        continue;
                    }

                    $this->attributeValueRepository->create([
                        'tenant_id' => $tenantId,
                        'organization_unit_id' => $payload['organization_unit_id'] ?? null,
                        'attribute_id' => $attributeId,
                        'value' => (string) $valueInput['value'],
                        'sort_order' => isset($valueInput['sort_order'])
                            ? (int) $valueInput['sort_order']
                            : 0,
                        'metadata' => is_array($valueInput['metadata'] ?? null)
                            ? $valueInput['metadata']
                            : null,
                    ]);
                }
            }

            $exists = $this->variantAttributeRepository->exists([
                'tenant_id' => $tenantId,
                'item_id' => $itemId,
                'attribute_id' => $attributeId,
            ]);

            if (! $exists) {
                $this->variantAttributeRepository->create([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $payload['organization_unit_id'] ?? null,
                    'item_id' => $itemId,
                    'attribute_id' => $attributeId,
                    'is_required' => (bool) ($attributeInput['is_required'] ?? false),
                    'display_order' => isset($attributeInput['display_order'])
                        ? (int) $attributeInput['display_order']
                        : 0,
                    'metadata' => is_array($attributeInput['metadata'] ?? null)
                        ? $attributeInput['metadata']
                        : null,
                ]);
            }
        }
    }

    /**
     * @param list<array<string, mixed>> $variants
     * @param array<string, mixed> $payload
     */
    private function syncVariants(
        int $tenantId,
        int $itemId,
        array $variants,
        array $payload,
        bool $replaceExisting,
    ): void {
        if ($replaceExisting) {
            $existingVariants = $this->variantRepository->list([
                'tenant_id' => $tenantId,
                'item_id' => $itemId,
            ]);

            foreach ($existingVariants as $variant) {
                $variantId = (int) $variant->id();
                $existingVariantValues = $this->variantAttributeValueRepository->list([
                    'tenant_id' => $tenantId,
                    'variant_id' => $variantId,
                ]);

                foreach ($existingVariantValues as $existingVariantValue) {
                    $this->variantAttributeValueRepository->delete((int) $existingVariantValue->id());
                }

                $this->variantRepository->delete($variantId);
            }
        }

        foreach ($variants as $variantInput) {
            $variant = $this->variantRepository->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $payload['organization_unit_id'] ?? null,
                'item_id' => $itemId,
                'sku' => $variantInput['sku'] ?? null,
                'name' => (string) ($variantInput['name'] ?? ''),
                'is_default' => (bool) ($variantInput['is_default'] ?? false),
                'is_active' => array_key_exists('is_active', $variantInput)
                    ? (bool) $variantInput['is_active']
                    : true,
                'cost_price' => $variantInput['cost_price'] ?? null,
                'sales_price' => $variantInput['sales_price'] ?? null,
                'metadata' => is_array($variantInput['metadata'] ?? null)
                    ? $variantInput['metadata']
                    : null,
            ]);

            $variantId = (int) $variant->id();
            $attributeValueIds = $variantInput['attribute_value_ids'] ?? [];
            if (! is_array($attributeValueIds)) {
                continue;
            }

            foreach ($attributeValueIds as $attributeValueId) {
                if (! is_numeric($attributeValueId)) {
                    continue;
                }

                $this->variantAttributeValueRepository->create([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $payload['organization_unit_id'] ?? null,
                    'variant_id' => $variantId,
                    'attribute_value_id' => (int) $attributeValueId,
                    'metadata' => null,
                ]);
            }
        }
    }

    /**
     * @param list<array<string, mixed>> $comboItems
     * @param array<string, mixed> $payload
     */
    private function syncComboItems(
        int $tenantId,
        int $itemId,
        array $comboItems,
        array $payload,
        bool $replaceExisting,
    ): void {
        if ($replaceExisting) {
            $existing = $this->comboItemRepository->list([
                'tenant_id' => $tenantId,
                'combo_item_id' => $itemId,
            ]);

            foreach ($existing as $record) {
                $this->comboItemRepository->delete((int) $record->id());
            }
        }

        foreach ($comboItems as $comboInput) {
            if (! isset($comboInput['component_item_id']) || ! is_numeric($comboInput['component_item_id'])) {
                continue;
            }

            $componentItemId = (int) $comboInput['component_item_id'];
            if ($componentItemId <= 0) {
                continue;
            }

            if ($this->itemRepository->findByIdInTenant($componentItemId, $tenantId) === null) {
                throw new InvalidArgumentException(sprintf(
                    'Component item %d does not exist in tenant %d.',
                    $componentItemId,
                    $tenantId,
                ));
            }

            if ($this->comboItemRepository->introducesCycle($tenantId, $itemId, $componentItemId)) {
                throw new InvalidArgumentException('This combo relation introduces a circular dependency.');
            }

            $uomId = isset($comboInput['uom_id']) && is_numeric($comboInput['uom_id'])
                ? (int) $comboInput['uom_id']
                : (isset($payload['base_uom_id']) ? (int) $payload['base_uom_id'] : 0);
            if ($uomId <= 0) {
                throw new InvalidArgumentException('Each combo item row requires a valid uom_id.');
            }

            $this->comboItemRepository->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $payload['organization_unit_id'] ?? null,
                'combo_item_id' => $itemId,
                'component_item_id' => $componentItemId,
                'component_variant_id' => isset($comboInput['component_variant_id'])
                    && is_numeric($comboInput['component_variant_id'])
                        ? (int) $comboInput['component_variant_id']
                        : null,
                'quantity' => isset($comboInput['quantity']) ? (float) $comboInput['quantity'] : 1,
                'uom_id' => $uomId,
                'sort_order' => isset($comboInput['sort_order']) ? (int) $comboInput['sort_order'] : 0,
                'metadata' => is_array($comboInput['metadata'] ?? null)
                    ? $comboInput['metadata']
                    : null,
            ]);
        }
    }

    /**
     * @param list<array<string, mixed>> $conversions
     * @param array<string, mixed> $payload
     */
    private function syncUomConversions(
        int $tenantId,
        int $itemId,
        array $conversions,
        array $payload,
        bool $replaceExisting,
    ): void {
        if ($replaceExisting) {
            $existingConversions = $this->uomConversionRepository->list([
                'tenant_id' => $tenantId,
                'item_id' => $itemId,
            ]);

            foreach ($existingConversions as $existingConversion) {
                $this->uomConversionRepository->delete((int) $existingConversion->id());
            }
        }

        foreach ($conversions as $conversionInput) {
            if (! isset($conversionInput['from_uom_id'], $conversionInput['to_uom_id'])) {
                continue;
            }

            $fromUomId = (int) $conversionInput['from_uom_id'];
            $toUomId = (int) $conversionInput['to_uom_id'];
            if ($fromUomId <= 0 || $toUomId <= 0 || $fromUomId === $toUomId) {
                continue;
            }

            $this->uomConversionRepository->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $payload['organization_unit_id'] ?? null,
                'item_id' => $itemId,
                'from_uom_id' => $fromUomId,
                'to_uom_id' => $toUomId,
                'factor' => isset($conversionInput['factor']) ? (float) $conversionInput['factor'] : 1,
                'offset' => isset($conversionInput['offset']) ? (float) $conversionInput['offset'] : 0,
                'is_bidirectional' => (bool) ($conversionInput['is_bidirectional'] ?? false),
                'is_active' => array_key_exists('is_active', $conversionInput)
                    ? (bool) $conversionInput['is_active']
                    : true,
                'metadata' => is_array($conversionInput['metadata'] ?? null)
                    ? $conversionInput['metadata']
                    : null,
            ]);
        }
    }
}
