<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;
use Modules\Inventory\DTOs\StockBalanceData;
use Modules\Inventory\Enums\InventoryStockLevel;
use Modules\Inventory\Http\Requests\InventoryLookupRequest;
use Modules\Inventory\Http\Resources\InventoryBatchResource;
use Modules\Inventory\Http\Resources\InventorySerialNumberResource;
use Modules\Inventory\Http\Resources\InventoryStockStateChangeResource;
use Modules\Inventory\Http\Resources\StockBalanceResource;
use Modules\Inventory\Models\InventoryBatch;
use Modules\Inventory\Models\InventorySerialNumber;
use Modules\Inventory\Models\InventoryStockBalance;
use Modules\Inventory\Models\InventoryStockStateChange;
use Modules\Inventory\Services\InventoryAvailabilityService;
use Modules\Inventory\Services\HistoricalStockBalanceService;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class StockController extends InventoryQueryController
{
    public function balances(InventoryLookupRequest $request, HistoricalStockBalanceService $history): AnonymousResourceCollection
    {
        if ($this->hasHistoricalDate($request)) {
            [$paginator, $summary] = $history->page($request, $this->asOfCutoff($request));

            return StockBalanceResource::collection($paginator)->additional(['summary' => $summary]);
        }

        $query = $this->balanceQuery($request);

        $summaryQuery = clone $query;
        $summary = [
            InventoryStockLevel::IN_STOCK->value => $this->stockLevelQuery(clone $summaryQuery, InventoryStockLevel::IN_STOCK)->count(),
            InventoryStockLevel::LOW_STOCK->value => $this->stockLevelQuery(clone $summaryQuery, InventoryStockLevel::LOW_STOCK)->count(),
            InventoryStockLevel::OUT_OF_STOCK->value => $this->stockLevelQuery(clone $summaryQuery, InventoryStockLevel::OUT_OF_STOCK)->count(),
        ];

        if ($request->filled('stock_level')) {
            $this->stockLevelQuery($query, InventoryStockLevel::from((string) $request->input('stock_level')));
        }

        return StockBalanceResource::collection(
            $query->orderBy('quantity_available')->orderBy('item_id')->paginate($request->perPage()),
        )->additional(['summary' => $summary]);
    }

    public function exportBalances(InventoryLookupRequest $request, HistoricalStockBalanceService $history): StreamedResponse
    {
        $rows = $this->hasHistoricalDate($request)
            ? $history->rows($request, $this->asOfCutoff($request))
            : $this->balanceQuery($request, true)
                ->orderBy('inventory_stock_balances.quantity_available')
                ->orderBy('inventory_stock_balances.item_id')
                ->lazy(500);

        return response()->streamDownload(static function () use ($rows): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                throw new \RuntimeException('Could not open the CSV output stream.');
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'Item', 'Code', 'SKU', 'Batch number', 'Warehouse', 'Location', 'Available stock', 'Unit',
                'Stock level', 'Reorder point', 'On hand', 'Reserved',
            ], ',', '"', '');

            foreach ($rows as $row) {
                $available = (float) $row->quantity_available;
                $reorderLevel = $row->item?->reorder_level;
                $stockLevel = $available <= 0
                    ? InventoryStockLevel::OUT_OF_STOCK
                    : ($reorderLevel !== null && $available <= (float) $reorderLevel
                        ? InventoryStockLevel::LOW_STOCK
                        : InventoryStockLevel::IN_STOCK);

                fputcsv($output, [
                    self::safeCsvText($row->item?->name),
                    self::safeCsvText($row->item?->code),
                    self::safeCsvText($row->item?->sku),
                    self::safeCsvText($row->batch?->batch_number),
                    self::safeCsvText($row->warehouse?->name),
                    self::safeCsvText($row->warehouseLocation?->name),
                    $row->quantity_available,
                    self::safeCsvText($row->baseUom?->symbol ?? $row->baseUom?->code ?? $row->baseUom?->name),
                    match ($stockLevel) {
                        InventoryStockLevel::IN_STOCK => 'In stock',
                        InventoryStockLevel::LOW_STOCK => 'Low stock',
                        InventoryStockLevel::OUT_OF_STOCK => 'Out of stock',
                    },
                    $reorderLevel,
                    $row->quantity_on_hand,
                    $row->quantity_reserved,
                ], ',', '"', '');
            }

            fclose($output);
        }, 'inventory-availability-'.($request->input('as_of') ?? now()->toDateString()).'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function hasHistoricalDate(InventoryLookupRequest $request): bool
    {
        if (! $request->filled('as_of')) {
            return false;
        }

        $timezone = (string) $request->input('as_of_timezone', config('app.timezone'));
        $asOf = (string) $request->input('as_of');
        $today = CarbonImmutable::now($timezone)->toDateString();
        if ($asOf > $today) {
            throw ValidationException::withMessages([
                'as_of' => ['The as-of date cannot be in the future.'],
            ]);
        }

        return $asOf < $today;
    }

    private function asOfCutoff(InventoryLookupRequest $request): CarbonImmutable
    {
        $timezone = (string) $request->input('as_of_timezone', config('app.timezone'));

        return CarbonImmutable::parse((string) $request->input('as_of'), $timezone)
            ->endOfDay()
            ->utc();
    }

    private function balanceQuery(InventoryLookupRequest $request, bool $applyStockLevel = false): Builder
    {
        $query = $this->scope(InventoryStockBalance::query(), $request)
            ->with(['item', 'baseUom', 'variant', 'warehouse', 'warehouseLocation', 'batch']);
        $this->filters($query, $request, [
            'item_id',
            'item_variant_id',
            'warehouse_id',
            'warehouse_location_id',
            'batch_id',
        ]);

        if ($request->filled('search')) {
            $search = '%'.trim((string) $request->input('search')).'%';
            $query->whereHas('item', static fn (Builder $itemQuery): Builder => $itemQuery
                ->where('name', 'like', $search)
                ->orWhere('code', 'like', $search)
                ->orWhere('sku', 'like', $search));
        }

        if ($applyStockLevel && $request->filled('stock_level')) {
            $this->stockLevelQuery($query, InventoryStockLevel::from((string) $request->input('stock_level')));
        }

        return $query;
    }

    private static function safeCsvText(?string $value): string
    {
        $value ??= '';

        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'".$value : $value;
    }

    private function stockLevelQuery(Builder $query, InventoryStockLevel $stockLevel): Builder
    {
        if ($stockLevel === InventoryStockLevel::OUT_OF_STOCK) {
            return $query->where('quantity_available', '<=', 0);
        }

        $query->where('quantity_available', '>', 0);

        if ($stockLevel === InventoryStockLevel::LOW_STOCK) {
            return $query->whereHas('item', static fn (Builder $itemQuery): Builder => $itemQuery
                ->whereNotNull('reorder_level')
                ->whereColumn('inventory_stock_balances.quantity_available', '<=', 'items.reorder_level'));
        }

        return $query->whereHas('item', static fn (Builder $itemQuery): Builder => $itemQuery
            ->where(static fn (Builder $reorderQuery): Builder => $reorderQuery
                ->whereNull('reorder_level')
                ->orWhereColumn('inventory_stock_balances.quantity_available', '>', 'items.reorder_level')));
    }

    public function availability(InventoryLookupRequest $request, InventoryAvailabilityService $service): JsonResponse
    {
        $request->validate([
            'item_id' => ['required', 'integer', 'min:1'],
            'warehouse_id' => ['required', 'integer', 'min:1'],
        ]);
        $result = $service->availability(new StockBalanceData(
            tenantId: $request->tenantId(),
            itemId: (int) $request->input('item_id'),
            warehouseId: (int) $request->input('warehouse_id'),
            organizationUnitId: $request->organizationUnitId(),
            itemVariantId: $request->filled('item_variant_id') ? (int) $request->input('item_variant_id') : null,
            warehouseLocationId: $request->filled('warehouse_location_id') ? (int) $request->input('warehouse_location_id') : null,
            batchId: $request->filled('batch_id') ? (int) $request->input('batch_id') : null,
        ));

        return response()->json(['data' => get_object_vars($result)]);
    }

    public function stateChanges(InventoryLookupRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(InventoryStockStateChange::query(), $request)
            ->with(['item', 'variant', 'warehouse', 'warehouseLocation', 'batch', 'serialNumber']);
        $this->filters($query, $request, [
            'item_id',
            'item_variant_id',
            'warehouse_id',
            'warehouse_location_id',
            'batch_id',
            'serial_number_id',
        ]);

        return InventoryStockStateChangeResource::collection($query->latest('id')->paginate($request->perPage()));
    }

    public function batches(InventoryLookupRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(InventoryBatch::query(), $request)->with(['item', 'variant']);
        $this->filters($query, $request, ['item_id', 'item_variant_id', 'status']);
        if ($request->filled('search')) {
            $search = '%'.trim((string) $request->input('search')).'%';
            $query->where(static fn (Builder $searchQuery): Builder => $searchQuery
                ->where('batch_number', 'like', $search)
                ->orWhere('lot_number', 'like', $search));
        }

        return InventoryBatchResource::collection($query->orderBy('batch_number')->paginate($request->perPage()));
    }

    public function serials(InventoryLookupRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(InventorySerialNumber::query(), $request)
            ->with(['item', 'variant', 'batch', 'warehouse', 'warehouseLocation']);
        $this->filters($query, $request, [
            'item_id',
            'item_variant_id',
            'warehouse_id',
            'warehouse_location_id',
            'batch_id',
            'status',
        ]);
        if ($request->filled('search')) {
            $query->where('serial_number', 'like', '%'.trim((string) $request->input('search')).'%');
        }

        return InventorySerialNumberResource::collection($query->orderBy('serial_number')->paginate($request->perPage()));
    }
}
