<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Core\Services\BaseService;
use Modules\Core\Services\TenantContext;
use Modules\Inventory\Enums\TransactionType;
use Modules\Inventory\Events\LowStockAlert;
use Modules\Inventory\Events\StockAdjusted;
use Modules\Inventory\Models\StockLedger;
use Modules\Inventory\Repositories\ProductRepository;
use Modules\Inventory\Repositories\StockLedgerRepository;
use Modules\Inventory\Repositories\StockLevelRepository;

/**
 * Stock Service
 *
 * Handles all stock management operations including adjustments,
 * transfers, reservations, and stock level calculations.
 */
class StockService extends BaseService
{
    /**
     * Constructor
     */
    public function __construct(
        TenantContext $tenantContext,
        protected StockLedgerRepository $repository,
        protected ProductRepository $productRepository,
        protected StockLevelRepository $stockLevelRepository
    ) {
        parent::__construct($tenantContext);
    }

    /**
     * Record a stock transaction.
     *
     * @param  array<string, mixed>  $data
     */
    public function recordTransaction(array $data): StockLedger
    {
        return DB::transaction(function () use ($data) {
            // Validate product exists using repository
            $product = $this->productRepository->findOrFail($data['product_id']);

            // Validate negative stock if configured
            if (! config('inventory.negative_stock_allowed', false)) {
                $currentStock = $this->getStockLevel(
                    $data['product_id'],
                    $data['warehouse_id'],
                    $data['location_id'] ?? null
                );

                $transactionType = TransactionType::from($data['transaction_type']);
                $effectiveQuantity = $data['quantity'] * $transactionType->multiplier();

                if ($currentStock + $effectiveQuantity < 0) {
                    throw new \RuntimeException('Insufficient stock. Negative stock not allowed.');
                }
            }

            // Set defaults
            $data['transaction_date'] = $data['transaction_date'] ?? now();
            $data['created_by'] = $data['created_by'] ?? Auth::id();
            $data['created_at'] = now();

            // Calculate total cost if unit cost provided
            if (isset($data['unit_cost']) && ! isset($data['total_cost'])) {
                $data['total_cost'] = $data['unit_cost'] * $data['quantity'];
            }

            // Create ledger entry
            $ledgerEntry = $this->repository->create($data);

            // Update stock level (materialized view)
            $this->updateStockLevel($ledgerEntry);

            // Dispatch event
            event(new StockAdjusted($ledgerEntry));

            // Check for low stock alert
            $this->checkLowStockAlert($product, $data['warehouse_id']);

            return $ledgerEntry->fresh(['product', 'warehouse']);
        });
    }

    /**
     * Adjust stock.
     *
     * @param  array<string, mixed>  $data
     */
    public function adjust(array $data): StockLedger
    {
        $data['transaction_type'] = $data['quantity'] > 0
            ? TransactionType::ADJUSTMENT_IN->value
            : TransactionType::ADJUSTMENT_OUT->value;

        $data['quantity'] = abs($data['quantity']);

        return $this->recordTransaction($data);
    }

    /**
     * Reserve stock for an order.
     *
     * @param  array<string, mixed>  $data
     */
    public function reserve(array $data): StockLedger
    {
        $data['transaction_type'] = TransactionType::RESERVATION->value;

        $ledger = $this->recordTransaction($data);

        // Update reserved quantity in stock level using repository
        $stockLevel = $this->stockLevelRepository->getByProductAndWarehouse(
            $data['product_id'],
            $data['warehouse_id'],
            $data['location_id'] ?? null
        );

        if ($stockLevel) {
            $stockLevel->increment('quantity_reserved', $data['quantity']);
            $stockLevel->decrement('quantity_available', $data['quantity']);
        }

        return $ledger;
    }

    /**
     * Allocate stock (convert reservation to allocation).
     *
     * @param  array<string, mixed>  $data
     */
    public function allocate(array $data): StockLedger
    {
        $data['transaction_type'] = TransactionType::ALLOCATION->value;

        $ledger = $this->recordTransaction($data);

        // Update allocated quantity in stock level using repository
        $stockLevel = $this->stockLevelRepository->getByProductAndWarehouse(
            $data['product_id'],
            $data['warehouse_id'],
            $data['location_id'] ?? null
        );

        if ($stockLevel) {
            $stockLevel->increment('quantity_allocated', $data['quantity']);
            $stockLevel->decrement('quantity_reserved', $data['quantity']);
        }

        return $ledger;
    }

    /**
     * Release reserved or allocated stock.
     *
     * @param  array<string, mixed>  $data
     */
    public function release(array $data): StockLedger
    {
        $data['transaction_type'] = TransactionType::RELEASE->value;

        $ledger = $this->recordTransaction($data);

        // Update stock level based on what's being released using repository
        $stockLevel = $this->stockLevelRepository->getByProductAndWarehouse(
            $data['product_id'],
            $data['warehouse_id'],
            $data['location_id'] ?? null
        );

        if ($stockLevel) {
            if ($data['release_type'] === 'reserved') {
                $stockLevel->decrement('quantity_reserved', $data['quantity']);
                $stockLevel->increment('quantity_available', $data['quantity']);
            } elseif ($data['release_type'] === 'allocated') {
                $stockLevel->decrement('quantity_allocated', $data['quantity']);
                $stockLevel->increment('quantity_available', $data['quantity']);
            }
        }

        return $ledger;
    }

    /**
     * Get current stock level for a product at a location.
     */
    public function getStockLevel(
        string $productId,
        string $warehouseId,
        ?string $locationId = null
    ): float {
        $stockLevel = $this->stockLevelRepository->getByProductAndWarehouse(
            $productId,
            $warehouseId,
            $locationId
        );

        return $stockLevel ? $stockLevel->quantity_available : 0;
    }

    /**
     * Get total stock for a product across all locations.
     */
    public function getTotalStock(string $productId): float
    {
        return $this->stockLevelRepository->getTotalAvailableStock($productId);
    }

    /**
     * Update stock level (materialized view) after a transaction.
     */
    protected function updateStockLevel(StockLedger $ledger): void
    {
        $stockLevel = $this->stockLevelRepository->findOrCreate([
            'product_id' => $ledger->product_id,
            'variant_id' => $ledger->variant_id,
            'warehouse_id' => $ledger->warehouse_id,
            'location_id' => $ledger->location_id,
            'tenant_id' => $ledger->tenant_id,
            'available' => 0,
            'reserved' => 0,
            'allocated' => 0,
            'damaged' => 0,
        ]);

        // Update quantity based on transaction type
        $effectiveQuantity = $ledger->effective_quantity;

        if ($ledger->transaction_type->isInbound()) {
            $stockLevel->increment('quantity_available', abs($effectiveQuantity));
        } elseif ($ledger->transaction_type->isOutbound()) {
            $stockLevel->decrement('quantity_available', abs($effectiveQuantity));
        }

        $stockLevel->last_movement_at = $ledger->transaction_date;
        $stockLevel->save();
    }

    /**
     * Check if stock is below reorder level and trigger alert.
     */
    protected function checkLowStockAlert(Product $product, string $warehouseId): void
    {
        if (! $product->reorder_level) {
            return;
        }

        $stockLevel = $this->getStockLevel($product->id, $warehouseId);

        if ($stockLevel <= $product->reorder_level) {
            event(new LowStockAlert($product, $warehouseId, $stockLevel));
        }
    }

    /**
     * Get stock movements for a product.
     *
     * @param  array<string, mixed>  $filters
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getStockMovements(string $productId, array $filters = [])
    {
        $query = $this->repository->newQuery()
            ->where('product_id', $productId)
            ->with(['product', 'warehouse', 'location']);

        if (isset($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (isset($filters['transaction_type'])) {
            $query->where('transaction_type', $filters['transaction_type']);
        }

        if (isset($filters['from_date'])) {
            $query->where('transaction_date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('transaction_date', '<=', $filters['to_date']);
        }

        $query->orderBy('transaction_date', 'desc');

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    /**
     * Calculate stock value (inventory valuation).
     */
    public function calculateStockValue(?string $warehouseId = null): float
    {
        $query = $this->stockLevelRepository->newQuery()
            ->join('products', 'stock_levels.product_id', '=', 'products.id');

        if ($warehouseId) {
            $query->where('stock_levels.warehouse_id', $warehouseId);
        }

        return $query->selectRaw('SUM(stock_levels.quantity_available * products.average_cost) as total_value')
            ->value('total_value') ?? 0;
    }

    /**
     * Reduce stock quantity (convenience method for sales)
     */
    public function reduceStock(
        string $productId,
        string $warehouseId,
        float $quantity,
        ?string $variantId = null,
        ?string $reason = null
    ): StockLedger {
        return $this->recordTransaction([
            'product_id' => $productId,
            'variant_id' => $variantId,
            'warehouse_id' => $warehouseId,
            'transaction_type' => TransactionType::SALE->value,
            'quantity' => $quantity,
            'reason' => $reason ?? 'Stock reduction',
        ]);
    }

    /**
     * Increase stock quantity (convenience method for returns/adjustments)
     */
    public function increaseStock(
        string $productId,
        string $warehouseId,
        float $quantity,
        ?string $variantId = null,
        ?string $reason = null
    ): StockLedger {
        return $this->recordTransaction([
            'product_id' => $productId,
            'variant_id' => $variantId,
            'warehouse_id' => $warehouseId,
            'transaction_type' => TransactionType::RETURN->value,
            'quantity' => $quantity,
            'reason' => $reason ?? 'Stock increase',
        ]);
    }

    /**
     * Get available quantity for a product (alias for getStockLevel)
     */
    public function getAvailableQuantity(
        string $productId,
        string $warehouseId,
        ?string $variantId = null
    ): float {
        return $this->getStockLevel($productId, $warehouseId, $variantId);
    }
}
