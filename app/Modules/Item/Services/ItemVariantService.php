<?php

declare(strict_types=1);

namespace Modules\Item\Services;

use Modules\Item\DTOs\ItemVariantData;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemVariant;
use Modules\Item\Validators\ItemValidationService;

final class ItemVariantService
{
    public function __construct(private readonly ItemValidationService $validator) {}

    public function create(Item $item, ItemVariantData $data): ItemVariant
    {
        $this->validator->validateVariant($item, $data);

        return ItemVariant::query()->create([
            'tenant_id' => $item->tenant_id,
            'organization_unit_id' => $item->organization_unit_id,
            'item_id' => $item->getKey(),
            'code' => $data->code,
            'sku' => $data->sku,
            'barcode' => $data->barcode,
            'name' => $data->name,
            'attributes' => $data->attributes,
            'is_active' => $data->isActive,
        ]);
    }

    public function update(Item $item, ItemVariant $variant, ItemVariantData $data): ItemVariant
    {
        $this->assertBelongsToItem($item, $variant);
        $this->validator->validateVariant($item, $data, (int) $variant->getKey());
        $variant->fill([
            'code' => $data->code,
            'sku' => $data->sku,
            'barcode' => $data->barcode,
            'name' => $data->name,
            'attributes' => $data->attributes,
            'is_active' => $data->isActive,
        ])->save();

        return $variant->refresh();
    }

    public function delete(Item $item, ItemVariant $variant): void
    {
        $this->assertBelongsToItem($item, $variant);
        $variant->delete();
    }

    private function assertBelongsToItem(Item $item, ItemVariant $variant): void
    {
        if ((int) $variant->item_id !== (int) $item->getKey()) {
            throw new \InvalidArgumentException('Item variant does not belong to the item.');
        }
    }
}
