<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Contracts;

use Modules\Inventory\Domain\Entities\StockBalance;
use Modules\Inventory\Domain\Entities\StockLedgerEntry;

interface StockLedgerRepositoryInterface
{
    /**
     * Append an immutable ledger entry (no update/delete allowed).
     */
    public function appendEntry(StockLedgerEntry $entry): StockLedgerEntry;

    /**
     * Retrieve paginated ledger entries for a product in a warehouse.
     */
    public function findEntries(
        int $tenantId,
        int $warehouseId,
        int $productId,
        int $page = 1,
        int $perPage = 25
    ): array;

    /**
     * Get the current stock balance row with a pessimistic lock (FOR UPDATE).
     * Returns null if no row exists yet.
     */
    public function lockBalance(int $tenantId, int $warehouseId, int $productId): ?StockBalance;

    /**
     * Persist the stock balance (upsert).
     */
    public function saveBalance(StockBalance $balance): StockBalance;

    /**
     * Get the current stock balance without locking (read-only).
     */
    public function findBalance(int $tenantId, int $warehouseId, int $productId): ?StockBalance;

    /**
     * Get all stock balances for a tenant, optionally filtered by warehouse.
     */
    public function findAllBalances(int $tenantId, ?int $warehouseId = null, int $page = 1, int $perPage = 25): array;

    /**
     * Get all stock balances for a specific product across all warehouses in the tenant.
     *
     * @return StockBalance[]
     */
    public function findBalancesByProductId(int $productId, int $tenantId): array;

    /**
     * Compute ABC analysis for all products in a tenant, optionally filtered by warehouse.
     * Returns items sorted by total inventory value descending with category (A/B/C).
     * A: top items representing ~80% of cumulative value
     * B: next items representing ~15% of cumulative value
     * C: remaining items representing ~5% of cumulative value
     */
    public function computeAbcAnalysis(int $tenantId, ?int $warehouseId): array;

    /**
     * Compute inventory valuation per product/warehouse (quantity_on_hand × average_cost).
     * Returns items sorted by total_value descending plus an overall grand_total_value.
     *
     * @return array{items: array<int,array{product_id:int,warehouse_id:int,quantity_on_hand:string,average_cost:string,total_value:string}>, grand_total_value: string}
     */
    public function computeValuation(int $tenantId, ?int $warehouseId): array;

    /**
     * Compute demand forecast based on historical outflow transactions (shipment + adjustment_out).
     * Groups by (product_id, warehouse_id) and computes average daily demand over `periodDays`.
     *
     * @return array<int,array{product_id:int,warehouse_id:int,total_outflow:string,period_days:int,avg_daily_demand:string,forecast_30_days:string}>
     */
    public function computeDemandForecast(int $tenantId, ?int $warehouseId, int $periodDays): array;

    /**
     * Compute inventory turnover rate per product over `periodDays`.
     * Turnover rate = COGS (shipment total_cost) / current inventory value.
     * Also returns annualised turnover rate and days-in-stock metric.
     *
     * @return array<int,array{product_id:int,warehouse_id:int|null,cogs:string,inventory_value:string,turnover_rate:string,annualised_turnover_rate:string,days_in_stock:string|null,period_days:int}>
     */
    public function computeTurnoverRate(int $tenantId, ?int $warehouseId, int $periodDays): array;

    /**
     * Compute carrying (holding) costs per product/warehouse over `periodDays`.
     * Carrying cost = quantity_on_hand × average_cost × carrying_rate / 365 × period_days
     * where `carrying_rate` is the annual holding cost rate (e.g. 0.25 = 25%).
     *
     * Returns items sorted by carrying_cost descending, plus a grand_total_carrying_cost.
     *
     * @return array{items: array<int,array{product_id:int,warehouse_id:int,quantity_on_hand:string,average_cost:string,inventory_value:string,carrying_cost:string,carrying_rate:string,period_days:int}>, grand_total_carrying_cost: string}
     */
    public function computeCarryingCosts(int $tenantId, ?int $warehouseId, int $periodDays, string $carryingRate): array;
}
