<?php

declare(strict_types=1);

namespace Modules\Item\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Item\DTOs\ItemCodeData;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemCode;
use Modules\Item\Validators\ItemValidationService;

final class ItemCodeService
{
    public function __construct(private readonly ItemValidationService $validator) {}

    public function create(Item $item, ItemCodeData $data): ItemCode
    {
        return DB::transaction(function () use ($item, $data): ItemCode {
            $lockedItem = $this->lockItem($item);
            $this->validator->validateCode($lockedItem, $data);
            $this->assertUnique($lockedItem, $data);

            if ($data->isPrimary) {
                $this->clearOtherPrimaryCodes($lockedItem);
            }

            return ItemCode::query()->create([
                'tenant_id' => $lockedItem->tenant_id,
                'organization_unit_id' => $lockedItem->organization_unit_id,
                'item_id' => $lockedItem->getKey(),
                'item_variant_id' => $data->itemVariantId,
                'code_type' => $data->codeType,
                'code' => $data->code,
                'party_type' => $data->partyType,
                'party_id' => $data->partyId,
                'is_primary' => $data->isPrimary,
            ]);
        });
    }

    public function update(Item $item, ItemCode $code, ItemCodeData $data): ItemCode
    {
        return DB::transaction(function () use ($item, $code, $data): ItemCode {
            $lockedItem = $this->lockItem($item);
            $this->assertBelongsToItem($lockedItem, $code);
            $this->validator->validateCode($lockedItem, $data);
            $this->assertUnique($lockedItem, $data, (int) $code->getKey());

            if ($data->isPrimary) {
                $this->clearOtherPrimaryCodes($lockedItem, (int) $code->getKey());
            }

            $code->fill([
                'item_variant_id' => $data->itemVariantId,
                'code_type' => $data->codeType,
                'code' => $data->code,
                'party_type' => $data->partyType,
                'party_id' => $data->partyId,
                'is_primary' => $data->isPrimary,
            ])->save();

            return $code->refresh()->load('variant');
        });
    }

    public function delete(Item $item, ItemCode $code): void
    {
        $this->assertBelongsToItem($item, $code);
        $code->delete();
    }

    private function assertUnique(Item $item, ItemCodeData $data, ?int $ignoreId = null): void
    {
        $query = ItemCode::query()
            ->where('tenant_id', $item->tenant_id)
            ->where('code_type', $data->codeType->value)
            ->where('code', $data->code);
        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }
        if ($query->exists()) {
            throw new InvalidArgumentException('Item alternative code already exists for this tenant and code type.');
        }
    }

    private function lockItem(Item $item): Item
    {
        return Item::query()
            ->where('tenant_id', $item->tenant_id)
            ->whereKey($item->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function clearOtherPrimaryCodes(Item $item, ?int $ignoreId = null): void
    {
        $query = ItemCode::query()
            ->where('item_id', $item->getKey());
        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        $query->update(['is_primary' => false]);
    }

    private function assertBelongsToItem(Item $item, ItemCode $code): void
    {
        if ((int) $code->item_id !== (int) $item->getKey()) {
            throw new InvalidArgumentException('Item code does not belong to the item.');
        }
    }
}
