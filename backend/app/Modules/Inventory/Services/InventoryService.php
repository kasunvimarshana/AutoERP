<?php

namespace App\Modules\Inventory\Services;

use App\Base\BaseService;
use App\Modules\Inventory\Repositories\InventoryRepository;
use App\Modules\Inventory\Repositories\StockLedgerRepository;
use App\Modules\Inventory\Repositories\BatchLotRepository;
use App\Modules\Inventory\DTOs\AdjustmentDTO;
use App\Modules\Inventory\DTOs\TransferDTO;
use App\Modules\Inventory\Events\StockAdjusted;
use App\Modules\Inventory\Events\StockTransferred;
use App\Modules\Inventory\Events\LowStockAlert;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Event;

class InventoryService extends BaseService
{
    protected StockLedgerRepository $ledgerRepository;
    protected BatchLotRepository $batchRepository;

    public function __construct(
        InventoryRepository $repository,
        StockLedgerRepository $ledgerRepository,
        BatchLotRepository $batchRepository
    ) {
        parent::__construct($repository);
        $this->ledgerRepository = $ledgerRepository;
        $this->batchRepository = $batchRepository;
    }

    public function adjustStock(AdjustmentDTO $dto): Model
    {
        return $this->executeInTransaction(function () use ($dto) {
            $inventory = $this->repository->findByVariantAndBranch(
                $dto->product_variant_id,
                $dto->branch_id
            );

            if (!$inventory) {
                $inventory = $this->repository->create([
                    'tenant_id' => $dto->tenant_id,
                    'branch_id' => $dto->branch_id,
                    'product_variant_id' => $dto->product_variant_id,
                    'quantity_on_hand' => 0,
                    'quantity_reserved' => 0,
                    'reorder_level' => 0,
                    'reorder_quantity' => 0,
                ]);
            }

            $ledgerEntry = $this->ledgerRepository->create([
                'tenant_id' => $dto->tenant_id,
                'branch_id' => $dto->branch_id,
                'inventory_id' => $inventory->id,
                'product_variant_id' => $dto->product_variant_id,
                'transaction_type' => 'adjustment',
                'quantity' => $dto->quantity,
                'unit_cost' => $dto->unit_cost,
                'reference_type' => $dto->reference_type,
                'reference_id' => $dto->reference_id,
                'batch_number' => $dto->batch_number,
                'lot_number' => $dto->lot_number,
                'expiry_date' => $dto->expiry_date,
                'notes' => $dto->notes,
                'created_by' => $dto->created_by,
            ]);

            $newQuantity = $inventory->quantity_on_hand + $dto->quantity;
            $inventory->update([
                'quantity_on_hand' => max(0, $newQuantity),
                'last_stock_date' => now(),
            ]);

            Event::dispatch(new StockAdjusted($inventory, $ledgerEntry));

            if ($inventory->isLowStock()) {
                Event::dispatch(new LowStockAlert($inventory));
            }

            return $ledgerEntry;
        });
    }

    public function transferStock(TransferDTO $dto): array
    {
        return $this->executeInTransaction(function () use ($dto) {
            $fromInventory = $this->repository->findByVariantAndBranch(
                $dto->product_variant_id,
                $dto->from_branch_id
            );

            if (!$fromInventory) {
                throw new \Exception('Source inventory not found');
            }

            if ($fromInventory->quantity_available < $dto->quantity) {
                throw new \Exception('Insufficient stock available for transfer');
            }

            $outLedgerEntry = $this->ledgerRepository->create([
                'tenant_id' => $dto->tenant_id,
                'branch_id' => $dto->from_branch_id,
                'inventory_id' => $fromInventory->id,
                'product_variant_id' => $dto->product_variant_id,
                'transaction_type' => 'out',
                'quantity' => $dto->quantity,
                'unit_cost' => $dto->unit_cost,
                'reference_type' => $dto->reference_type,
                'reference_id' => $dto->reference_id,
                'batch_number' => $dto->batch_number,
                'lot_number' => $dto->lot_number,
                'notes' => $dto->notes . ' (Transfer OUT)',
                'created_by' => $dto->created_by,
            ]);

            $fromInventory->update([
                'quantity_on_hand' => $fromInventory->quantity_on_hand - $dto->quantity,
                'last_stock_date' => now(),
            ]);

            $toInventory = $this->repository->findByVariantAndBranch(
                $dto->product_variant_id,
                $dto->to_branch_id
            );

            if (!$toInventory) {
                $toInventory = $this->repository->create([
                    'tenant_id' => $dto->tenant_id,
                    'branch_id' => $dto->to_branch_id,
                    'product_variant_id' => $dto->product_variant_id,
                    'quantity_on_hand' => 0,
                    'quantity_reserved' => 0,
                    'reorder_level' => 0,
                    'reorder_quantity' => 0,
                ]);
            }

            $inLedgerEntry = $this->ledgerRepository->create([
                'tenant_id' => $dto->tenant_id,
                'branch_id' => $dto->to_branch_id,
                'inventory_id' => $toInventory->id,
                'product_variant_id' => $dto->product_variant_id,
                'transaction_type' => 'in',
                'quantity' => $dto->quantity,
                'unit_cost' => $dto->unit_cost,
                'reference_type' => $dto->reference_type,
                'reference_id' => $dto->reference_id,
                'batch_number' => $dto->batch_number,
                'lot_number' => $dto->lot_number,
                'notes' => $dto->notes . ' (Transfer IN)',
                'created_by' => $dto->created_by,
            ]);

            $toInventory->update([
                'quantity_on_hand' => $toInventory->quantity_on_hand + $dto->quantity,
                'last_stock_date' => now(),
            ]);

            Event::dispatch(new StockTransferred($fromInventory, $toInventory, $dto->quantity));

            if ($fromInventory->isLowStock()) {
                Event::dispatch(new LowStockAlert($fromInventory));
            }

            return [
                'from_inventory' => $fromInventory,
                'to_inventory' => $toInventory,
                'out_ledger' => $outLedgerEntry,
                'in_ledger' => $inLedgerEntry,
            ];
        });
    }

    public function reserveStock(string $variantId, string $branchId, int $quantity): bool
    {
        return $this->executeInTransaction(function () use ($variantId, $branchId, $quantity) {
            $inventory = $this->repository->findByVariantAndBranch($variantId, $branchId);

            if (!$inventory) {
                throw new \Exception('Inventory not found');
            }

            if ($inventory->quantity_available < $quantity) {
                throw new \Exception('Insufficient stock available for reservation');
            }

            return $inventory->update([
                'quantity_reserved' => $inventory->quantity_reserved + $quantity,
            ]);
        });
    }

    public function releaseReservation(string $variantId, string $branchId, int $quantity): bool
    {
        return $this->executeInTransaction(function () use ($variantId, $branchId, $quantity) {
            $inventory = $this->repository->findByVariantAndBranch($variantId, $branchId);

            if (!$inventory) {
                throw new \Exception('Inventory not found');
            }

            $newReserved = max(0, $inventory->quantity_reserved - $quantity);

            return $inventory->update([
                'quantity_reserved' => $newReserved,
            ]);
        });
    }

    public function getInventoryByBranch(string $branchId): Collection
    {
        return $this->repository->getByBranch($branchId, ['productVariant.product', 'branch']);
    }

    public function getStockLedger(string $variantId, string $branchId): Collection
    {
        return $this->ledgerRepository->getByVariant($variantId, ['productVariant', 'branch', 'creator']);
    }

    public function getLowStockItems(string $branchId): Collection
    {
        return $this->repository->getLowStock($branchId, ['productVariant.product', 'branch']);
    }
}
