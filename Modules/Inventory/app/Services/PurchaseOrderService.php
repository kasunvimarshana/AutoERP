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
use Modules\Inventory\Enums\POStatus;
use Modules\Inventory\Models\PurchaseOrder;
use Modules\Inventory\Repositories\InventoryItemRepository;
use Modules\Inventory\Repositories\PurchaseOrderRepository;
use Modules\Inventory\Repositories\StockMovementRepository;

/**
 * Purchase Order Service
 *
 * Contains business logic for Purchase Order operations
 */
class PurchaseOrderService extends BaseService
{
    /**
     * PurchaseOrderService constructor
     */
    public function __construct(
        PurchaseOrderRepository $repository,
        private readonly InventoryItemRepository $itemRepository,
        private readonly StockMovementRepository $stockMovementRepository
    ) {
        parent::__construct($repository);
    }

    /**
     * Create a new purchase order
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     * @throws ServiceException
     */
    public function create(array $data): mixed
    {
        DB::beginTransaction();
        try {
            // Generate PO number if not provided
            if (! isset($data['po_number'])) {
                $data['po_number'] = $this->generateUniquePONumber();
            } elseif ($this->repository->poNumberExists($data['po_number'])) {
                throw ValidationException::withMessages([
                    'po_number' => ['The PO number has already been taken.'],
                ]);
            }

            // Set created_by
            $data['created_by'] = Auth::id();

            // Calculate totals from items
            $items = $data['items'] ?? [];
            $subtotal = collect($items)->sum(fn ($item) => $item['quantity'] * $item['unit_cost']);
            $data['subtotal'] = $subtotal;
            $data['tax'] = 0; // Implement tax calculation as needed
            $data['total'] = $subtotal;

            // Create PO
            $itemsData = $data['items'];
            unset($data['items']);

            /** @var PurchaseOrder */
            $po = $this->repository->create($data);

            // Create PO items
            foreach ($itemsData as $itemData) {
                $po->items()->create([
                    'item_id' => $itemData['item_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_cost' => $itemData['unit_cost'],
                    'total' => $itemData['quantity'] * $itemData['unit_cost'],
                ]);
            }

            DB::commit();

            return $this->repository->findWithItems($po->id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PO creation failed', ['error' => $e->getMessage()]);
            throw new ServiceException('Failed to create purchase order: '.$e->getMessage());
        }
    }

    /**
     * Update purchase order
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ServiceException
     */
    public function update(int $id, array $data): mixed
    {
        DB::beginTransaction();
        try {
            /** @var PurchaseOrder */
            $po = $this->repository->findOrFail($id);

            if (! $po->isEditable()) {
                throw new ServiceException('Purchase order cannot be edited in current status');
            }

            // Update PO items if provided
            if (isset($data['items'])) {
                $itemsData = $data['items'];
                unset($data['items']);

                // Delete existing items and recreate
                $po->items()->delete();

                $subtotal = 0;
                foreach ($itemsData as $itemData) {
                    $po->items()->create([
                        'item_id' => $itemData['item_id'],
                        'quantity' => $itemData['quantity'],
                        'unit_cost' => $itemData['unit_cost'],
                        'total' => $itemData['quantity'] * $itemData['unit_cost'],
                    ]);
                    $subtotal += $itemData['quantity'] * $itemData['unit_cost'];
                }

                $data['subtotal'] = $subtotal;
                $data['total'] = $subtotal;
            }

            $result = parent::update($id, $data);

            DB::commit();

            return $this->repository->findWithItems($id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PO update failed', ['error' => $e->getMessage()]);
            throw new ServiceException('Failed to update purchase order: '.$e->getMessage());
        }
    }

    /**
     * Approve purchase order
     *
     * @throws ServiceException
     */
    public function approve(int $id): PurchaseOrder
    {
        DB::beginTransaction();
        try {
            /** @var PurchaseOrder */
            $po = $this->repository->findOrFail($id);

            if ($po->status !== POStatus::PENDING->value) {
                throw new ServiceException('Only pending purchase orders can be approved');
            }

            $this->repository->update($id, [
                'status' => POStatus::APPROVED->value,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            DB::commit();

            return $this->repository->findOrFail($id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PO approval failed', ['error' => $e->getMessage()]);
            throw new ServiceException('Failed to approve purchase order: '.$e->getMessage());
        }
    }

    /**
     * Receive purchase order items
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ServiceException
     */
    public function receiveItems(int $id, array $data): PurchaseOrder
    {
        DB::beginTransaction();
        try {
            /** @var PurchaseOrder */
            $po = $this->repository->findWithItems($id);

            if (! $po->canReceive()) {
                throw new ServiceException('Purchase order must be approved before receiving items');
            }

            $receivedItems = $data['items']; // Array of ['po_item_id' => quantity]

            foreach ($receivedItems as $poItemId => $quantityReceived) {
                $poItem = $po->items()->findOrFail($poItemId);

                if ($quantityReceived <= 0) {
                    continue;
                }

                $remainingQty = $poItem->getRemainingQuantity();
                if ($quantityReceived > $remainingQty) {
                    throw new ServiceException("Cannot receive more than ordered quantity for item {$poItem->inventoryItem->item_name}");
                }

                // Update received quantity
                $poItem->update([
                    'received_quantity' => $poItem->received_quantity + $quantityReceived,
                ]);

                // Increment stock
                $this->itemRepository->incrementStock($poItem->item_id, $quantityReceived);

                // Create stock movement
                $this->stockMovementRepository->create([
                    'item_id' => $poItem->item_id,
                    'branch_id' => $po->branch_id,
                    'movement_type' => MovementType::IN->value,
                    'quantity' => $quantityReceived,
                    'unit_cost' => $poItem->unit_cost,
                    'reference_type' => PurchaseOrder::class,
                    'reference_id' => $po->id,
                    'notes' => "Received from PO {$po->po_number}",
                    'created_by' => Auth::id(),
                ]);
            }

            // Check if all items are fully received
            $allReceived = $po->items->every(fn ($item) => $item->isFullyReceived());

            if ($allReceived) {
                $po->update(['status' => POStatus::RECEIVED->value]);
            }

            DB::commit();

            return $this->repository->findWithItems($id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PO receiving failed', ['error' => $e->getMessage()]);
            throw new ServiceException('Failed to receive items: '.$e->getMessage());
        }
    }

    /**
     * Search purchase orders
     *
     * @param  array<string, mixed>  $filters
     */
    public function search(array $filters): mixed
    {
        return $this->repository->search($filters);
    }

    /**
     * Get by status
     */
    public function getByStatus(string $status): mixed
    {
        return $this->repository->getByStatus($status);
    }

    /**
     * Generate unique PO number
     *
     * @throws ServiceException
     */
    private function generateUniquePONumber(): string
    {
        $prefix = 'PO';
        $date = now()->format('Ymd');
        $attempts = 0;
        $maxAttempts = 100;

        do {
            $sequence = str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
            $poNumber = "{$prefix}-{$date}-{$sequence}";
            $attempts++;

            if ($attempts >= $maxAttempts) {
                throw new ServiceException('Unable to generate unique PO number');
            }
        } while ($this->repository->poNumberExists($poNumber));

        return $poNumber;
    }
}
