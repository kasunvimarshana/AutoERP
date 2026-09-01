<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Core\Services\DecimalMath;
use Modules\Inventory\DTOs\BatchPriceData;
use Modules\Inventory\DTOs\SupersedeBatchPriceData;
use Modules\Inventory\Models\InventoryBatch;
use Modules\Inventory\Models\InventoryBatchPriceRevision;
use Modules\Item\DTOs\ItemPriceData;
use Modules\Item\Enums\ItemPriceType;
use Modules\Item\Validators\ItemValidationService;

final class BatchPriceService
{
    private const OPEN_ENDED_EFFECTIVE_DATE = '9999-12-31';

    public function __construct(
        private readonly ItemValidationService $itemValidator,
        private readonly DecimalMath $math,
    ) {}

    public function create(int $tenantId, BatchPriceData $data): InventoryBatchPriceRevision
    {
        return DB::transaction(function () use ($tenantId, $data): InventoryBatchPriceRevision {
            $batch = $this->lockBatch($tenantId, $data->batchId);
            $this->validate($batch, $data);
            $this->lockCurrentPrices($batch);
            $this->assertNoOverlap($batch, $data);

            return $this->createRevision($batch, $data, (string) Str::uuid(), 1, null, null);
        }, 3);
    }

    public function supersede(
        int $tenantId,
        InventoryBatchPriceRevision $price,
        SupersedeBatchPriceData $data,
    ): InventoryBatchPriceRevision {
        return DB::transaction(function () use ($tenantId, $price, $data): InventoryBatchPriceRevision {
            $reason = trim($data->correctionReason);
            $batch = $this->lockBatch($tenantId, $data->price->batchId);
            $current = InventoryBatchPriceRevision::query()
                ->where('tenant_id', $tenantId)
                ->where('batch_id', $batch->getKey())
                ->whereKey($price->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            if ($current->recorded_to !== null || (int) $current->row_version !== $data->expectedVersion) {
                throw ValidationException::withMessages([
                    'expected_version' => ['The batch price changed after it was loaded. Reload and review the latest revision.'],
                ]);
            }

            $this->validate($batch, $data->price);
            $this->lockCurrentPrices($batch);
            $this->assertNoOverlap($batch, $data->price, (int) $current->getKey());

            $recordedAt = CarbonImmutable::now();
            $updated = InventoryBatchPriceRevision::query()
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
                    'expected_version' => ['The batch price changed while it was being saved. Reload and try again.'],
                ]);
            }

            return $this->createRevision(
                $batch,
                $data->price,
                (string) $current->lineage_key,
                (int) $current->revision_no + 1,
                (int) $current->getKey(),
                $reason,
                $recordedAt,
            );
        }, 3);
    }

    public function resolve(
        InventoryBatch $batch,
        ItemPriceType $priceType,
        int $currencyId,
        int $uomId,
        ?int $organizationUnitId = null,
        ?string $date = null,
    ): ?InventoryBatchPriceRevision {
        $effectiveDate = $date ?? CarbonImmutable::now()->toDateString();

        return InventoryBatchPriceRevision::query()
            ->where('tenant_id', $batch->tenant_id)
            ->where('batch_id', $batch->getKey())
            ->where('price_type', $priceType->value)
            ->where('currency_id', $currencyId)
            ->where('uom_id', $uomId)
            ->whereNull('recorded_to')
            ->when(
                $organizationUnitId === null,
                fn (Builder $query) => $query->whereNull('organization_unit_id'),
                fn (Builder $query) => $query->where(fn (Builder $scope): Builder => $scope
                    ->whereNull('organization_unit_id')
                    ->orWhere('organization_unit_id', $organizationUnitId)),
            )
            ->where('effective_from', '<=', $effectiveDate)
            ->where(fn (Builder $query): Builder => $query->whereNull('effective_to')->orWhere('effective_to', '>=', $effectiveDate))
            ->when($organizationUnitId !== null, fn (Builder $query) => $query->orderByRaw('case when organization_unit_id = ? then 0 else 1 end', [$organizationUnitId]))
            ->orderByDesc('effective_from')
            ->orderByDesc('recorded_from')
            ->first();
    }

    private function validate(InventoryBatch $batch, BatchPriceData $data): void
    {
        if (! in_array($data->priceType, [ItemPriceType::Sales, ItemPriceType::Service], true)) {
            throw ValidationException::withMessages(['price_type' => ['Batch prices support sales and service price types only.']]);
        }
        $batch->loadMissing('item');
        $this->itemValidator->validatePrice($batch->item, new ItemPriceData(
            priceType: $data->priceType,
            amount: $data->amount,
            currencyId: $data->currencyId,
            uomId: $data->uomId,
            organizationUnitId: $data->organizationUnitId,
            effectiveFrom: $data->effectiveFrom,
            itemVariantId: $batch->item_variant_id === null ? null : (int) $batch->item_variant_id,
            effectiveTo: $data->effectiveTo,
        ));
    }

    private function lockBatch(int $tenantId, int $batchId): InventoryBatch
    {
        return InventoryBatch::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($batchId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function lockCurrentPrices(InventoryBatch $batch): void
    {
        InventoryBatchPriceRevision::query()
            ->where('tenant_id', $batch->tenant_id)
            ->where('batch_id', $batch->getKey())
            ->whereNull('recorded_to')
            ->lockForUpdate()
            ->get(['id']);
    }

    private function assertNoOverlap(InventoryBatch $batch, BatchPriceData $data, ?int $ignoreId = null): void
    {
        $query = InventoryBatchPriceRevision::query()
            ->where('tenant_id', $batch->tenant_id)
            ->where('batch_id', $batch->getKey())
            ->whereNull('recorded_to')
            ->where('scope_key', $this->scopeKey($data))
            ->where('effective_from', '<=', $data->effectiveTo ?? self::OPEN_ENDED_EFFECTIVE_DATE)
            ->where(fn (Builder $period): Builder => $period->whereNull('effective_to')->orWhere('effective_to', '>=', $data->effectiveFrom));
        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }
        if ($query->exists()) {
            throw ValidationException::withMessages([
                'effective_from' => ['The effective period overlaps an existing batch price for the same scope.'],
            ]);
        }
    }

    private function createRevision(
        InventoryBatch $batch,
        BatchPriceData $data,
        string $lineageKey,
        int $revisionNo,
        ?int $supersedesId,
        ?string $reason,
        ?CarbonImmutable $recordedFrom = null,
    ): InventoryBatchPriceRevision {
        $price = InventoryBatchPriceRevision::query()->create([
            'row_version' => 1,
            'tenant_id' => $batch->tenant_id,
            'organization_unit_id' => $data->organizationUnitId,
            'batch_id' => $batch->getKey(),
            'price_type' => $data->priceType,
            'currency_id' => $data->currencyId,
            'uom_id' => $data->uomId,
            'amount' => $this->math->normalize($data->amount),
            'effective_from' => $data->effectiveFrom,
            'effective_to' => $data->effectiveTo,
            'scope_key' => $this->scopeKey($data),
            'lineage_key' => $lineageKey,
            'revision_no' => $revisionNo,
            'supersedes_price_id' => $supersedesId,
            'recorded_from' => $recordedFrom ?? CarbonImmutable::now(),
            'recorded_to' => null,
            'correction_reason' => $reason,
        ]);

        return $price->load(['batch.item', 'organizationUnit', 'currency', 'uom']);
    }

    private function scopeKey(BatchPriceData $data): string
    {
        return hash('sha256', implode('|', [
            $data->batchId,
            $data->organizationUnitId ?? 'global',
            $data->priceType->value,
            $data->currencyId,
            $data->uomId,
        ]));
    }
}
