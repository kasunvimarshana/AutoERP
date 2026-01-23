<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use App\Core\Exceptions\ServiceException;
use App\Core\Services\BaseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\Inventory\Enums\MovementType;
use Modules\Inventory\Models\InventoryItem;
use Modules\Inventory\Repositories\InventoryItemRepository;
use Modules\Inventory\Repositories\StockMovementRepository;

/**
 * Inventory Item Service
 *
 * Contains business logic for Inventory operations
 */
class InventoryItemService extends BaseService
{
    /**
     * InventoryItemService constructor
     */
    public function __construct(
        InventoryItemRepository $repository,
        private readonly StockMovementRepository $stockMovementRepository
    ) {
        parent::__construct($repository);
    }

    /**
     * Create a new inventory item
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function create(array $data): mixed
    {
        // Validate item code uniqueness for branch
        if ($this->repository->itemCodeExistsForBranch($data['item_code'], $data['branch_id'])) {
            throw ValidationException::withMessages([
                'item_code' => ['The item code already exists for this branch.'],
            ]);
        }

        return parent::create($data);
    }

    /**
     * Update inventory item
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function update(int $id, array $data): mixed
    {
        $item = $this->repository->findOrFail($id);

        // Validate item code uniqueness if changed
        if (isset($data['item_code']) && $data['item_code'] !== $item->item_code) {
            if ($this->repository->itemCodeExistsForBranch($data['item_code'], $item->branch_id, $id)) {
                throw ValidationException::withMessages([
                    'item_code' => ['The item code already exists for this branch.'],
                ]);
            }
        }

        return parent::update($id, $data);
    }

    /**
     * Adjust stock with movement tracking
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ServiceException
     */
    public function adjustStock(int $itemId, array $data): InventoryItem
    {
        DB::beginTransaction();
        try {
            /** @var InventoryItem */
            $item = $this->repository->findOrFail($itemId);

            if ($item->is_dummy_item) {
                throw new ServiceException('Cannot adjust stock for dummy items');
            }

            $newQuantity = $data['new_quantity'];
            $quantityDifference = $newQuantity - $item->stock_on_hand;

            // Update stock
            $this->repository->updateStock($itemId, $newQuantity);

            // Create stock movement record
            $this->stockMovementRepository->create([
                'item_id' => $itemId,
                'branch_id' => $item->branch_id,
                'movement_type' => MovementType::ADJUSTMENT->value,
                'quantity' => $quantityDifference,
                'unit_cost' => $item->unit_cost,
                'notes' => $data['notes'] ?? 'Stock adjustment',
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return $this->repository->findOrFail($itemId);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Stock adjustment failed', ['error' => $e->getMessage(), 'item_id' => $itemId]);
            throw new ServiceException('Failed to adjust stock: '.$e->getMessage());
        }
    }

    /**
     * Transfer stock between branches
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ServiceException
     */
    public function transferStock(array $data): array
    {
        DB::beginTransaction();
        try {
            $fromItemId = $data['from_item_id'];
            $toBranchId = $data['to_branch_id'];
            $quantity = $data['quantity'];

            /** @var InventoryItem */
            $fromItem = $this->repository->findOrFail($fromItemId);

            if ($fromItem->is_dummy_item) {
                throw new ServiceException('Cannot transfer dummy items');
            }

            if ($fromItem->stock_on_hand < $quantity) {
                throw new ServiceException('Insufficient stock for transfer');
            }

            // Find or create item in destination branch
            $toItem = $this->repository->findByItemCodeAndBranch($fromItem->item_code, $toBranchId);

            if (! $toItem) {
                // Create item in destination branch
                $toItem = $this->repository->create([
                    'branch_id' => $toBranchId,
                    'item_code' => $fromItem->item_code,
                    'item_name' => $fromItem->item_name,
                    'category' => $fromItem->category,
                    'description' => $fromItem->description,
                    'unit_of_measure' => $fromItem->unit_of_measure,
                    'reorder_level' => $fromItem->reorder_level,
                    'reorder_quantity' => $fromItem->reorder_quantity,
                    'unit_cost' => $fromItem->unit_cost,
                    'selling_price' => $fromItem->selling_price,
                    'stock_on_hand' => 0,
                    'is_dummy_item' => false,
                ]);
            }

            // Deduct from source
            $this->repository->decrementStock($fromItemId, $quantity);

            // Add to destination
            $this->repository->incrementStock($toItem->id, $quantity);

            // Create movement records
            $movementData = [
                'movement_type' => MovementType::TRANSFER->value,
                'quantity' => $quantity,
                'unit_cost' => $fromItem->unit_cost,
                'from_branch_id' => $fromItem->branch_id,
                'to_branch_id' => $toBranchId,
                'notes' => $data['notes'] ?? 'Inter-branch transfer',
                'created_by' => Auth::id(),
            ];

            // Outbound movement from source
            $this->stockMovementRepository->create([
                ...$movementData,
                'item_id' => $fromItemId,
                'branch_id' => $fromItem->branch_id,
                'quantity' => -$quantity,
            ]);

            // Inbound movement to destination
            $this->stockMovementRepository->create([
                ...$movementData,
                'item_id' => $toItem->id,
                'branch_id' => $toBranchId,
            ]);

            DB::commit();

            return [
                'from_item' => $this->repository->findOrFail($fromItemId),
                'to_item' => $this->repository->findOrFail($toItem->id),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Stock transfer failed', ['error' => $e->getMessage()]);
            throw new ServiceException('Failed to transfer stock: '.$e->getMessage());
        }
    }

    /**
     * Get low stock items
     */
    public function getLowStockItems(?int $branchId = null): mixed
    {
        return $this->repository->getLowStockItems($branchId);
    }

    /**
     * Get items by branch
     */
    public function getByBranch(int $branchId): mixed
    {
        return $this->repository->getByBranch($branchId);
    }

    /**
     * Search items
     *
     * @param  array<string, mixed>  $filters
     */
    public function search(array $filters): mixed
    {
        return $this->repository->search($filters);
    }

    /**
     * Get reorder suggestions
     */
    public function getReorderSuggestions(?int $branchId = null): array
    {
        $lowStockItems = $this->repository->getLowStockItems($branchId);

        return $lowStockItems->map(function (InventoryItem $item) {
            return [
                'item_id' => $item->id,
                'item_code' => $item->item_code,
                'item_name' => $item->item_name,
                'current_stock' => $item->stock_on_hand,
                'reorder_level' => $item->reorder_level,
                'suggested_quantity' => $item->reorder_quantity,
                'unit_cost' => $item->unit_cost,
            ];
        })->toArray();
    }
}
