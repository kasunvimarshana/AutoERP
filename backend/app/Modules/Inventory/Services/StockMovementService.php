<?php

namespace App\Modules\Inventory\Services;

use App\Enums\StockMovementType;
use App\Modules\Inventory\DTOs\StockMovementDTO;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\StockLedger;
use App\Modules\Inventory\Repositories\ProductRepository;
use App\Modules\Inventory\Repositories\StockLedgerRepository;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

class StockMovementService extends BaseService
{
    public function __construct(
        protected StockLedgerRepository $stockLedgerRepository,
        protected ProductRepository $productRepository
    ) {}

    /**
     * Record a stock movement (purchase, sale, adjustment, transfer)
     *
     * @throws \Exception
     */
    public function recordMovement(StockMovementDTO $dto): StockLedger
    {
        return DB::transaction(function () use ($dto) {
            // Validate product exists
            $product = $this->productRepository->find($dto->productId);
            if (! $product) {
                throw new \Exception('Product not found');
            }

            // Validate warehouse exists if provided
            if ($dto->warehouseId) {
                $warehouse = DB::table('warehouses')->find($dto->warehouseId);
                if (! $warehouse) {
                    throw new \Exception('Warehouse not found');
                }
            }

            // Calculate quantity based on movement type
            $quantity = $this->calculateQuantity($dto->movementType, $dto->quantity);

            // Get current balance
            $currentBalance = $this->stockLedgerRepository->getCurrentBalance(
                $dto->productId,
                $dto->warehouseId
            );

            // Validate stock availability for outbound movements
            if ($quantity < 0 && abs($quantity) > $currentBalance) {
                throw new \Exception('Insufficient stock. Available: '.$currentBalance);
            }

            // Calculate new balance
            $newBalance = $currentBalance + $quantity;

            // Create stock ledger entry
            $ledgerEntry = $this->stockLedgerRepository->create([
                'product_id' => $dto->productId,
                'warehouse_id' => $dto->warehouseId,
                'movement_type' => $dto->movementType->value,
                'quantity' => $quantity,
                'unit_cost' => $dto->unitCost,
                'running_balance' => $newBalance,
                'reference_type' => $dto->referenceType,
                'reference_id' => $dto->referenceId,
                'batch_number' => $dto->batchNumber,
                'lot_number' => $dto->lotNumber,
                'serial_number' => $dto->serialNumber,
                'expiry_date' => $dto->expiryDate,
                'notes' => $dto->notes,
            ]);

            // Update product's current stock (for quick access)
            $this->updateProductStock($dto->productId, $dto->warehouseId);

            return $ledgerEntry;
        });
    }

    /**
     * Record a stock adjustment
     */
    public function adjustStock(
        int $productId,
        int $warehouseId,
        float $adjustmentQuantity,
        ?string $reason = null
    ): StockLedger {
        $movementType = $adjustmentQuantity >= 0
            ? StockMovementType::ADJUSTMENT_IN
            : StockMovementType::ADJUSTMENT_OUT;

        $dto = new StockMovementDTO(
            productId: $productId,
            movementType: $movementType,
            quantity: abs($adjustmentQuantity),
            warehouseId: $warehouseId,
            notes: $reason
        );

        return $this->recordMovement($dto);
    }

    /**
     * Transfer stock between warehouses
     */
    public function transferStock(
        int $productId,
        int $fromWarehouseId,
        int $toWarehouseId,
        float $quantity,
        ?string $notes = null
    ): array {
        return DB::transaction(function () use ($productId, $fromWarehouseId, $toWarehouseId, $quantity, $notes) {
            // Record outbound from source warehouse
            $outboundDto = new StockMovementDTO(
                productId: $productId,
                movementType: StockMovementType::TRANSFER_OUT,
                quantity: $quantity,
                warehouseId: $fromWarehouseId,
                notes: $notes
            );
            $outbound = $this->recordMovement($outboundDto);

            // Record inbound to destination warehouse
            $inboundDto = new StockMovementDTO(
                productId: $productId,
                movementType: StockMovementType::TRANSFER_IN,
                quantity: $quantity,
                warehouseId: $toWarehouseId,
                referenceType: 'stock_ledger',
                referenceId: $outbound->id,
                notes: $notes
            );
            $inbound = $this->recordMovement($inboundDto);

            return [
                'outbound' => $outbound,
                'inbound' => $inbound,
            ];
        });
    }

    /**
     * Calculate quantity based on movement type
     */
    protected function calculateQuantity(StockMovementType $movementType, float $quantity): float
    {
        return match ($movementType) {
            StockMovementType::PURCHASE,
            StockMovementType::ADJUSTMENT_IN,
            StockMovementType::TRANSFER_IN,
            StockMovementType::RETURN_IN => abs($quantity),

            StockMovementType::SALE,
            StockMovementType::ADJUSTMENT_OUT,
            StockMovementType::TRANSFER_OUT,
            StockMovementType::RETURN_OUT => -abs($quantity),
        };
    }

    /**
     * Update product's current stock
     */
    protected function updateProductStock(int $productId, ?int $warehouseId = null): void
    {
        $currentStock = $this->stockLedgerRepository->getCurrentBalance($productId, $warehouseId);

        // Update product's stock level
        // This is for quick access, the ledger remains the source of truth
        DB::table('products')
            ->where('id', $productId)
            ->update([
                'current_stock' => $currentStock,
                'updated_at' => now(),
            ]);
    }
}
