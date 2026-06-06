<?php

declare(strict_types=1);

namespace Modules\Item\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Item\DTOs\ItemPriceData;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemPrice;
use Modules\Item\Validators\ItemValidationService;

final class ItemPriceService
{
    public function __construct(
        private readonly ItemValidationService $validator,
        private readonly DecimalMath $math,
    ) {}

    public function create(Item $item, ItemPriceData $data): ItemPrice
    {
        $this->validator->validatePrice($item, $data);

        return ItemPrice::query()->create([
            'tenant_id' => $item->tenant_id,
            'organization_unit_id' => $item->organization_unit_id,
            'item_id' => $item->getKey(),
            'item_variant_id' => $data->itemVariantId,
            'price_type' => $data->priceType,
            'currency_id' => $data->currencyId,
            'uom_id' => $data->uomId,
            'amount' => $this->math->normalize($data->amount),
            'effective_from' => $data->effectiveFrom,
            'effective_to' => $data->effectiveTo,
            'is_active' => $data->isActive,
        ]);
    }

    public function update(Item $item, ItemPrice $price, ItemPriceData $data): ItemPrice
    {
        $this->assertBelongsToItem($item, $price);
        $this->validator->validatePrice($item, $data);
        $price->fill([
            'item_variant_id' => $data->itemVariantId,
            'price_type' => $data->priceType,
            'currency_id' => $data->currencyId,
            'uom_id' => $data->uomId,
            'amount' => $this->math->normalize($data->amount),
            'effective_from' => $data->effectiveFrom,
            'effective_to' => $data->effectiveTo,
            'is_active' => $data->isActive,
        ])->save();

        return $price->refresh()->load(['variant', 'currency', 'uom']);
    }

    public function delete(Item $item, ItemPrice $price): void
    {
        $this->assertBelongsToItem($item, $price);
        $price->delete();
    }

    private function assertBelongsToItem(Item $item, ItemPrice $price): void
    {
        if ((int) $price->item_id !== (int) $item->getKey()) {
            throw new \InvalidArgumentException('Item price does not belong to the item.');
        }
    }
}
