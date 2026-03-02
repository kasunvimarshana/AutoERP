<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Inventory\Application\Commands\AdjustStockCommand;
use Modules\Inventory\Application\Commands\ReceiveStockCommand;
use Modules\Inventory\Application\Commands\ReleaseReservationCommand;
use Modules\Inventory\Application\Commands\ReserveStockCommand;
use Modules\Inventory\Application\Commands\ReturnStockCommand;
use Modules\Inventory\Application\Commands\ShipStockCommand;
use Modules\Inventory\Application\Commands\TransferStockCommand;
use Modules\Inventory\Application\Handlers\AdjustStockHandler;
use Modules\Inventory\Application\Handlers\ReceiveStockHandler;
use Modules\Inventory\Application\Handlers\ReleaseReservationHandler;
use Modules\Inventory\Application\Handlers\ReserveStockHandler;
use Modules\Inventory\Application\Handlers\ReturnStockHandler;
use Modules\Inventory\Application\Handlers\ShipStockHandler;
use Modules\Inventory\Application\Handlers\TransferStockHandler;
use Modules\Inventory\Domain\Contracts\StockLedgerRepositoryInterface;
use Modules\Inventory\Domain\Entities\StockBalance;
use Modules\Inventory\Domain\Entities\StockLedgerEntry;
use Modules\Product\Domain\Contracts\ProductRepositoryInterface;

/**
 * Service orchestrating all inventory stock operations.
 *
 * Controllers must interact with the inventory domain exclusively through this
 * service. Read operations are fulfilled directly via the repository contract;
 * write operations are delegated to the appropriate command handlers.
 */
class InventoryService
{
    public function __construct(
        private readonly StockLedgerRepositoryInterface $stockLedgerRepository,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ReceiveStockHandler $receiveStockHandler,
        private readonly AdjustStockHandler $adjustStockHandler,
        private readonly TransferStockHandler $transferStockHandler,
        private readonly ShipStockHandler $shipStockHandler,
        private readonly ReserveStockHandler $reserveStockHandler,
        private readonly ReleaseReservationHandler $releaseReservationHandler,
        private readonly ReturnStockHandler $returnStockHandler,
    ) {}

    /**
     * Retrieve paginated stock balances for the given tenant, optionally
     * filtered by warehouse.
     *
     * @return array{items: StockBalance[], current_page: int, last_page: int, per_page: int, total: int}
     */
    public function listStockBalances(int $tenantId, ?int $warehouseId, int $page, int $perPage): array
    {
        return $this->stockLedgerRepository->findAllBalances($tenantId, $warehouseId, $page, $perPage);
    }

    /**
     * Retrieve paginated ledger entries for a product in a warehouse.
     *
     * @return array{items: StockLedgerEntry[], current_page: int, last_page: int, per_page: int, total: int}
     */
    public function listLedgerEntries(int $tenantId, int $warehouseId, int $productId, int $page, int $perPage): array
    {
        return $this->stockLedgerRepository->findEntries($tenantId, $warehouseId, $productId, $page, $perPage);
    }

    /**
     * Receive stock into a warehouse and return the new ledger entry.
     */
    public function receiveStock(ReceiveStockCommand $command): StockLedgerEntry
    {
        return $this->receiveStockHandler->handle($command);
    }

    /**
     * Adjust stock in a warehouse and return the new ledger entry.
     */
    public function adjustStock(AdjustStockCommand $command): StockLedgerEntry
    {
        return $this->adjustStockHandler->handle($command);
    }

    /**
     * Transfer stock between warehouses and return both ledger entries.
     *
     * @return array{transfer_out: StockLedgerEntry, transfer_in: StockLedgerEntry}
     */
    public function transferStock(TransferStockCommand $command): array
    {
        return $this->transferStockHandler->handle($command);
    }

    /**
     * Ship (fulfil) stock out of a warehouse and return the new ledger entry.
     */
    public function shipStock(ShipStockCommand $command): StockLedgerEntry
    {
        return $this->shipStockHandler->handle($command);
    }

    /**
     * Reserve available stock and return the updated StockBalance.
     */
    public function reserveStock(ReserveStockCommand $command): StockBalance
    {
        return $this->reserveStockHandler->handle($command);
    }

    /**
     * Release a previously reserved stock and return the updated StockBalance.
     */
    public function releaseReservation(ReleaseReservationCommand $command): StockBalance
    {
        return $this->releaseReservationHandler->handle($command);
    }

    /**
     * Return stock to a warehouse and return the new ledger entry.
     */
    public function returnStock(ReturnStockCommand $command): StockLedgerEntry
    {
        return $this->returnStockHandler->handle($command);
    }

    /**
     * Compute ABC analysis for products in a tenant, optionally filtered by warehouse.
     */
    public function computeAbcAnalysis(int $tenantId, ?int $warehouseId): array
    {
        return $this->stockLedgerRepository->computeAbcAnalysis($tenantId, $warehouseId);
    }

    /**
     * Compute inventory valuation (quantity_on_hand × average_cost) per product/warehouse.
     *
     * @return array{items: array<int,array<string,mixed>>, grand_total_value: string}
     */
    public function computeValuation(int $tenantId, ?int $warehouseId): array
    {
        return $this->stockLedgerRepository->computeValuation($tenantId, $warehouseId);
    }

    /**
     * Compute demand forecast from historical outflow transactions.
     *
     * @return array<int,array<string,mixed>>
     */
    public function computeDemandForecast(int $tenantId, ?int $warehouseId, int $periodDays): array
    {
        return $this->stockLedgerRepository->computeDemandForecast($tenantId, $warehouseId, $periodDays);
    }

    /**
     * Compute inventory turnover rate per product over a given period.
     *
     * @return array<int,array<string,mixed>>
     */
    public function computeTurnoverRate(int $tenantId, ?int $warehouseId, int $periodDays): array
    {
        return $this->stockLedgerRepository->computeTurnoverRate($tenantId, $warehouseId, $periodDays);
    }

    /**
     * Compute carrying (holding) costs per product/warehouse over `periodDays`.
     *
     * @return array{items: array<int,array<string,mixed>>, grand_total_carrying_cost: string}
     */
    public function computeCarryingCosts(int $tenantId, ?int $warehouseId, int $periodDays, string $carryingRate): array
    {
        return $this->stockLedgerRepository->computeCarryingCosts($tenantId, $warehouseId, $periodDays, $carryingRate);
    }

    /**
     * Look up a product by barcode and return the product with its current stock balances.
     *
     * Returns null when no product matches the given barcode within the tenant.
     *
     * @return array{product_id: int, sku: string, name: string, barcode: string, uom: string, balances: StockBalance[]}|null
     */
    public function scanByBarcode(int $tenantId, string $barcode): ?array
    {
        $product = $this->productRepository->findByBarcode($barcode, $tenantId);

        if ($product === null) {
            return null;
        }

        $balances = $this->stockLedgerRepository->findBalancesByProductId($product->id, $tenantId);

        return [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'barcode' => $product->barcode,
            'uom' => $product->uom,
            'balances' => $balances,
        ];
    }
}
