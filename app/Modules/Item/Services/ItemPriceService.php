<?php

declare(strict_types=1);

namespace Modules\Item\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Core\Services\DecimalMath;
use Modules\Item\DTOs\ItemPriceData;
use Modules\Item\DTOs\SupersedeItemPriceData;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemPrice;
use Modules\Item\Validators\ItemValidationService;

final class ItemPriceService
{
    private const OPEN_ENDED_EFFECTIVE_DATE = '9999-12-31';
    private const MAX_CORRECTION_REASON_LENGTH = 1000;

    public function __construct(
        private readonly ItemValidationService $validator,
        private readonly DecimalMath $math,
    ) {}

    public function create(Item $item, ItemPriceData $data): ItemPrice
    {
        return DB::transaction(function () use ($item, $data): ItemPrice {
            $lockedItem = $this->lockItem($item);
            $this->validator->validatePrice($lockedItem, $data);
            $this->lockCurrentPrices($lockedItem);
            $this->assertNoOverlap($lockedItem, $data);

            return $this->createRevision(
                item: $lockedItem,
                data: $data,
                lineageKey: (string) Str::uuid(),
                revisionNo: 1,
                supersedesPriceId: null,
                correctionReason: null,
            );
        }, 3);
    }

    public function supersede(Item $item, ItemPrice $price, SupersedeItemPriceData $data): ItemPrice
    {
        return DB::transaction(function () use ($item, $price, $data): ItemPrice {
            $correctionReason = trim($data->correctionReason);
            if ($correctionReason === '') {
                throw ValidationException::withMessages([
                    'correction_reason' => ['A correction reason is required to supersede an item price revision.'],
                ]);
            }
            if (strlen($correctionReason) > self::MAX_CORRECTION_REASON_LENGTH) {
                throw ValidationException::withMessages([
                    'correction_reason' => ['The correction reason may not exceed '.self::MAX_CORRECTION_REASON_LENGTH.' characters.'],
                ]);
            }
            $lockedItem = $this->lockItem($item);
            $current = ItemPrice::query()
                ->where('tenant_id', $lockedItem->tenant_id)
                ->where('item_id', $lockedItem->getKey())
                ->whereKey($price->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($current->recorded_to !== null) {
                throw ValidationException::withMessages([
                    'expected_version' => ['Only the current recorded revision can be superseded. Reload the price history.'],
                ]);
            }
            if ((int) $current->row_version !== $data->expectedVersion) {
                throw ValidationException::withMessages([
                    'expected_version' => ['The item price changed after it was loaded. Reload and review the latest revision.'],
                ]);
            }

            $this->validator->validatePrice($lockedItem, $data->price);
            $this->lockCurrentPrices($lockedItem);
            $this->assertNoOverlap($lockedItem, $data->price, (int) $current->getKey());

            $recordedAt = CarbonImmutable::now();
            $updated = ItemPrice::query()
                ->whereKey($current->getKey())
                ->where('row_version', $data->expectedVersion)
                ->whereNull('recorded_to')
                ->update([
                    'recorded_to' => $recordedAt,
                    'row_version' => $data->expectedVersion + 1,
                    'updated_at' => $recordedAt,
                ]);
            if ($updated !== 1) {
                throw ValidationException::withMessages([
                    'expected_version' => ['The item price changed while the correction was being saved. Reload and try again.'],
                ]);
            }

            return $this->createRevision(
                item: $lockedItem,
                data: $data->price,
                lineageKey: (string) $current->lineage_key,
                revisionNo: (int) $current->revision_no + 1,
                supersedesPriceId: (int) $current->getKey(),
                correctionReason: $correctionReason,
                recordedFrom: $recordedAt,
            );
        }, 3);
    }

    private function lockItem(Item $item): Item
    {
        return Item::query()
            ->where('tenant_id', $item->tenant_id)
            ->whereKey($item->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function lockCurrentPrices(Item $item): void
    {
        ItemPrice::query()
            ->where('tenant_id', $item->tenant_id)
            ->where('item_id', $item->getKey())
            ->whereNull('recorded_to')
            ->lockForUpdate()
            ->get(['id']);
    }

    private function assertNoOverlap(Item $item, ItemPriceData $data, ?int $ignorePriceId = null): void
    {
        $query = ItemPrice::query()
            ->where('tenant_id', $item->tenant_id)
            ->where('item_id', $item->getKey())
            ->whereNull('recorded_to')
            ->where('scope_key', $this->scopeKey($data))
            ->where('effective_from', '<=', $data->effectiveTo ?? self::OPEN_ENDED_EFFECTIVE_DATE)
            ->where(function (Builder $period) use ($data): void {
                $period->whereNull('effective_to')->orWhere('effective_to', '>=', $data->effectiveFrom);
            });
        if ($ignorePriceId !== null) {
            $query->whereKeyNot($ignorePriceId);
        }
        if ($query->exists()) {
            throw ValidationException::withMessages([
                'effective_from' => ['The effective period overlaps an existing current price revision for the same scope.'],
                'effective_to' => ['Choose a non-overlapping period or supersede the existing revision.'],
            ]);
        }
    }

    private function createRevision(
        Item $item,
        ItemPriceData $data,
        string $lineageKey,
        int $revisionNo,
        ?int $supersedesPriceId,
        ?string $correctionReason,
        ?CarbonImmutable $recordedFrom = null,
    ): ItemPrice {
        $price = ItemPrice::query()->create([
            'row_version' => 1,
            'tenant_id' => $item->tenant_id,
            'organization_unit_id' => $data->organizationUnitId,
            'item_id' => $item->getKey(),
            'item_variant_id' => $data->itemVariantId,
            'price_type' => $data->priceType,
            'currency_id' => $data->currencyId,
            'uom_id' => $data->uomId,
            'amount' => $this->math->normalize($data->amount),
            'effective_from' => $data->effectiveFrom,
            'effective_to' => $data->effectiveTo,
            'scope_key' => $this->scopeKey($data),
            'lineage_key' => $lineageKey,
            'revision_no' => $revisionNo,
            'supersedes_price_id' => $supersedesPriceId,
            'recorded_from' => $recordedFrom ?? CarbonImmutable::now(),
            'recorded_to' => null,
            'correction_reason' => $correctionReason === null ? null : trim($correctionReason),
        ]);

        return $price->load(['organizationUnit', 'variant', 'currency', 'uom']);
    }

    private function scopeKey(ItemPriceData $data): string
    {
        return ItemPriceScopeKey::for(
            organizationUnitId: $data->organizationUnitId,
            itemVariantId: $data->itemVariantId,
            priceType: $data->priceType,
            currencyId: $data->currencyId,
            uomId: $data->uomId,
        );
    }
}
