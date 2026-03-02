<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Repositories;

use App\Shared\Abstractions\BaseRepository;
use Modules\Inventory\Domain\Contracts\StockLedgerRepositoryInterface;
use Modules\Inventory\Domain\Entities\StockBalance;
use Modules\Inventory\Domain\Entities\StockLedgerEntry;
use Modules\Inventory\Infrastructure\Models\StockBalanceModel;
use Modules\Inventory\Infrastructure\Models\StockLedgerEntryModel;

class StockLedgerRepository extends BaseRepository implements StockLedgerRepositoryInterface
{
    protected function model(): string
    {
        return StockLedgerEntryModel::class;
    }

    public function appendEntry(StockLedgerEntry $entry): StockLedgerEntry
    {
        $model = new StockLedgerEntryModel;
        $model->tenant_id = $entry->tenantId;
        $model->warehouse_id = $entry->warehouseId;
        $model->product_id = $entry->productId;
        $model->transaction_type = $entry->transactionType;
        $model->quantity = $entry->quantity;
        $model->unit_cost = $entry->unitCost;
        $model->total_cost = $entry->totalCost;
        $model->reference_type = $entry->referenceType;
        $model->reference_id = $entry->referenceId;
        $model->notes = $entry->notes;
        $model->created_at = now();
        $model->save();

        return $this->entryToDomain($model);
    }

    public function findEntries(
        int $tenantId,
        int $warehouseId,
        int $productId,
        int $page = 1,
        int $perPage = 25
    ): array {
        $paginator = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->getCollection()
                ->map(fn (StockLedgerEntryModel $m) => $this->entryToDomain($m))
                ->all(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
        ];
    }

    public function lockBalance(int $tenantId, int $warehouseId, int $productId): ?StockBalance
    {
        $model = StockBalanceModel::query()
            ->where('tenant_id', $tenantId)
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        return $model ? $this->balanceToDomain($model) : null;
    }

    public function saveBalance(StockBalance $balance): StockBalance
    {
        if ($balance->id !== null) {
            $model = StockBalanceModel::query()->findOrFail($balance->id);
        } else {
            $model = StockBalanceModel::query()
                ->where('tenant_id', $balance->tenantId)
                ->where('warehouse_id', $balance->warehouseId)
                ->where('product_id', $balance->productId)
                ->first() ?? new StockBalanceModel;
        }

        $model->tenant_id = $balance->tenantId;
        $model->warehouse_id = $balance->warehouseId;
        $model->product_id = $balance->productId;
        $model->quantity_on_hand = $balance->quantityOnHand;
        $model->quantity_reserved = $balance->quantityReserved;
        $model->average_cost = $balance->averageCost;
        $model->save();

        return $this->balanceToDomain($model);
    }

    public function findBalance(int $tenantId, int $warehouseId, int $productId): ?StockBalance
    {
        $model = StockBalanceModel::query()
            ->where('tenant_id', $tenantId)
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->first();

        return $model ? $this->balanceToDomain($model) : null;
    }

    public function findAllBalances(int $tenantId, ?int $warehouseId = null, int $page = 1, int $perPage = 25): array
    {
        $query = StockBalanceModel::query()
            ->where('tenant_id', $tenantId);

        if ($warehouseId !== null) {
            $query->where('warehouse_id', $warehouseId);
        }

        $paginator = $query->orderBy('product_id')->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->getCollection()
                ->map(fn (StockBalanceModel $m) => $this->balanceToDomain($m))
                ->all(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
        ];
    }

    public function findBalancesByProductId(int $productId, int $tenantId): array
    {
        $rows = StockBalanceModel::query()
            ->where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->orderBy('warehouse_id')
            ->get();

        return $rows->map(fn (StockBalanceModel $m) => $this->balanceToDomain($m))->all();
    }

    public function computeAbcAnalysis(int $tenantId, ?int $warehouseId): array
    {
        $query = StockBalanceModel::query()
            ->where('tenant_id', $tenantId);

        if ($warehouseId !== null) {
            $query->where('warehouse_id', $warehouseId);
        }

        $rows = $query->get();

        $items = [];
        foreach ($rows as $row) {
            $qoh = bcadd((string) $row->quantity_on_hand, '0', 4);
            $avgCost = bcadd((string) $row->average_cost, '0', 4);
            $totalValue = bcmul($qoh, $avgCost, 4);

            $items[] = [
                'product_id' => $row->product_id,
                'warehouse_id' => $row->warehouse_id,
                'quantity_on_hand' => $qoh,
                'average_cost' => $avgCost,
                'total_value' => $totalValue,
                'abc_category' => '',
            ];
        }

        usort($items, fn ($a, $b) => bccomp($b['total_value'], $a['total_value'], 4));

        $grandTotal = array_reduce(
            $items,
            fn (string $carry, array $item) => bcadd($carry, $item['total_value'], 4),
            '0'
        );

        if (bccomp($grandTotal, '0', 4) === 0) {
            foreach ($items as &$item) {
                $item['abc_category'] = 'C';
            }
            unset($item);

            return $items;
        }

        $cumulative = '0';
        foreach ($items as &$item) {
            $cumulative = bcadd($cumulative, $item['total_value'], 4);
            $cumulativePct = bcdiv(bcmul($cumulative, '100', 4), $grandTotal, 4);

            if (bccomp($cumulativePct, '80', 4) <= 0) {
                $item['abc_category'] = 'A';
            } elseif (bccomp($cumulativePct, '95', 4) <= 0) {
                $item['abc_category'] = 'B';
            } else {
                $item['abc_category'] = 'C';
            }
        }
        unset($item);

        return $items;
    }

    public function computeValuation(int $tenantId, ?int $warehouseId): array
    {
        $query = StockBalanceModel::query()->where('tenant_id', $tenantId);

        if ($warehouseId !== null) {
            $query->where('warehouse_id', $warehouseId);
        }

        $rows = $query->orderBy('product_id')->get();

        $items = [];
        $grandTotal = '0';

        foreach ($rows as $row) {
            $qoh = bcadd((string) $row->quantity_on_hand, '0', 4);
            $avgCost = bcadd((string) $row->average_cost, '0', 4);
            $totalValue = bcmul($qoh, $avgCost, 4);
            $grandTotal = bcadd($grandTotal, $totalValue, 4);

            $items[] = [
                'product_id' => $row->product_id,
                'warehouse_id' => $row->warehouse_id,
                'quantity_on_hand' => $qoh,
                'average_cost' => $avgCost,
                'total_value' => $totalValue,
            ];
        }

        usort($items, fn (array $a, array $b) => bccomp($b['total_value'], $a['total_value'], 4));

        return [
            'items' => $items,
            'grand_total_value' => $grandTotal,
        ];
    }

    public function computeDemandForecast(int $tenantId, ?int $warehouseId, int $periodDays): array
    {
        $since = now()->subDays($periodDays)->startOfDay();

        $query = StockLedgerEntryModel::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('transaction_type', ['shipment', 'adjustment_out'])
            ->where('created_at', '>=', $since);

        if ($warehouseId !== null) {
            $query->where('warehouse_id', $warehouseId);
        }

        $entries = $query->get();

        $grouped = [];

        foreach ($entries as $entry) {
            $key = $entry->product_id.'_'.$entry->warehouse_id;

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'product_id' => $entry->product_id,
                    'warehouse_id' => $entry->warehouse_id,
                    'total_outflow' => '0',
                ];
            }

            $grouped[$key]['total_outflow'] = bcadd(
                $grouped[$key]['total_outflow'],
                (string) $entry->quantity,
                4
            );
        }

        $periodStr = (string) $periodDays;
        $items = [];

        foreach ($grouped as $item) {
            $avgDailyDemand = bcdiv($item['total_outflow'], $periodStr, 4);
            $forecast30Days = bcmul($avgDailyDemand, '30', 4);

            $items[] = [
                'product_id' => $item['product_id'],
                'warehouse_id' => $item['warehouse_id'],
                'total_outflow' => $item['total_outflow'],
                'period_days' => $periodDays,
                'avg_daily_demand' => $avgDailyDemand,
                'forecast_30_days' => $forecast30Days,
            ];
        }

        return $items;
    }

    public function computeTurnoverRate(int $tenantId, ?int $warehouseId, int $periodDays): array
    {
        $since = now()->subDays($periodDays)->startOfDay();

        $cogsQuery = StockLedgerEntryModel::query()
            ->where('tenant_id', $tenantId)
            ->where('transaction_type', 'shipment')
            ->where('created_at', '>=', $since);

        if ($warehouseId !== null) {
            $cogsQuery->where('warehouse_id', $warehouseId);
        }

        $shipments = $cogsQuery->get();

        $cogs = [];

        foreach ($shipments as $entry) {
            $productId = $entry->product_id;

            if (! isset($cogs[$productId])) {
                $cogs[$productId] = '0';
            }

            $cogs[$productId] = bcadd(
                $cogs[$productId],
                (string) $entry->total_cost,
                4
            );
        }

        $balanceQuery = StockBalanceModel::query()->where('tenant_id', $tenantId);

        if ($warehouseId !== null) {
            $balanceQuery->where('warehouse_id', $warehouseId);
        }

        $balances = $balanceQuery->get();

        $inventoryValues = [];

        foreach ($balances as $balance) {
            $productId = $balance->product_id;
            $qoh = bcadd((string) $balance->quantity_on_hand, '0', 4);
            $avgCost = bcadd((string) $balance->average_cost, '0', 4);
            $value = bcmul($qoh, $avgCost, 4);

            if (! isset($inventoryValues[$productId])) {
                $inventoryValues[$productId] = '0';
            }

            $inventoryValues[$productId] = bcadd($inventoryValues[$productId], $value, 4);
        }

        $periodStr = (string) $periodDays;
        $items = [];

        foreach ($cogs as $productId => $cogsValue) {
            $inventoryValue = $inventoryValues[$productId] ?? '0';

            if (bccomp($inventoryValue, '0', 4) > 0) {
                $turnoverRate = bcdiv($cogsValue, $inventoryValue, 4);
                $annualisedRate = bcdiv(bcmul($turnoverRate, '365', 4), $periodStr, 4);
                $cogsDailyRate = bcdiv($cogsValue, $periodStr, 4);
                $daysInStock = bccomp($cogsDailyRate, '0', 4) > 0
                    ? bcdiv($inventoryValue, $cogsDailyRate, 1)
                    : null;
            } else {
                $turnoverRate = '0.0000';
                $annualisedRate = '0.0000';
                $daysInStock = null;
            }

            $items[] = [
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'cogs' => $cogsValue,
                'inventory_value' => $inventoryValue,
                'turnover_rate' => $turnoverRate,
                'annualised_turnover_rate' => $annualisedRate,
                'days_in_stock' => $daysInStock,
                'period_days' => $periodDays,
            ];
        }

        return $items;
    }

    public function computeCarryingCosts(int $tenantId, ?int $warehouseId, int $periodDays, string $carryingRate): array
    {
        $query = StockBalanceModel::query()->where('tenant_id', $tenantId);

        if ($warehouseId !== null) {
            $query->where('warehouse_id', $warehouseId);
        }

        $rows = $query->orderBy('product_id')->get();

        $items = [];
        $grandTotal = '0';
        $periodStr = (string) $periodDays;

        foreach ($rows as $row) {
            $qoh = bcadd((string) $row->quantity_on_hand, '0', 4);
            $avgCost = bcadd((string) $row->average_cost, '0', 4);
            $inventoryValue = bcmul($qoh, $avgCost, 4);

            // carrying_cost = inventory_value × carrying_rate / 365 × period_days
            $annualCost = bcmul($inventoryValue, $carryingRate, 4);
            $dailyCost = bcdiv($annualCost, '365', 8);
            $carryingCost = bcmul($dailyCost, $periodStr, 4);
            $grandTotal = bcadd($grandTotal, $carryingCost, 4);

            $items[] = [
                'product_id' => $row->product_id,
                'warehouse_id' => $row->warehouse_id,
                'quantity_on_hand' => $qoh,
                'average_cost' => $avgCost,
                'inventory_value' => $inventoryValue,
                'carrying_cost' => $carryingCost,
                'carrying_rate' => $carryingRate,
                'period_days' => $periodDays,
            ];
        }

        usort($items, fn (array $a, array $b) => bccomp($b['carrying_cost'], $a['carrying_cost'], 4));

        return [
            'items' => $items,
            'grand_total_carrying_cost' => $grandTotal,
        ];
    }

    private function entryToDomain(StockLedgerEntryModel $model): StockLedgerEntry
    {
        return new StockLedgerEntry(
            id: $model->id,
            tenantId: $model->tenant_id,
            warehouseId: $model->warehouse_id,
            productId: $model->product_id,
            transactionType: $model->transaction_type,
            quantity: bcadd((string) $model->quantity, '0', 4),
            unitCost: bcadd((string) $model->unit_cost, '0', 4),
            totalCost: bcadd((string) $model->total_cost, '0', 4),
            referenceType: $model->reference_type,
            referenceId: $model->reference_id,
            notes: $model->notes,
            createdAt: $model->created_at?->toIso8601String(),
        );
    }

    private function balanceToDomain(StockBalanceModel $model): StockBalance
    {
        return new StockBalance(
            id: $model->id,
            tenantId: $model->tenant_id,
            warehouseId: $model->warehouse_id,
            productId: $model->product_id,
            quantityOnHand: bcadd((string) $model->quantity_on_hand, '0', 4),
            quantityReserved: bcadd((string) $model->quantity_reserved, '0', 4),
            averageCost: bcadd((string) $model->average_cost, '0', 4),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }
}
