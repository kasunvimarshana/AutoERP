<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Services;

use App\Core\Services\BaseService;
use App\Core\Exceptions\ServiceException;
use App\Modules\Warehouse\Repositories\WarehouseTransferRepositoryInterface;
use App\Modules\Warehouse\Models\WarehouseTransfer;
use App\Modules\Warehouse\Models\WarehouseTransferItem;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WarehouseTransferService extends BaseService
{
    protected InventoryService $inventoryService;

    public function __construct(
        WarehouseTransferRepositoryInterface $repository,
        InventoryService $inventoryService
    ) {
        parent::__construct($repository);
        $this->inventoryService = $inventoryService;
    }

    public function create(array $data)
    {
        DB::beginTransaction();
        
        try {
            if (isset($data['transfer_number'])) {
                $existing = $this->repository->findByNumber($data['transfer_number']);
                if ($existing) {
                    throw new ServiceException('Transfer number already exists');
                }
            } else {
                $data['transfer_number'] = $this->generateTransferNumber();
            }

            if ($data['source_branch_id'] === $data['destination_branch_id'] && 
                (!isset($data['source_location_id']) || !isset($data['destination_location_id']) ||
                $data['source_location_id'] === $data['destination_location_id'])) {
                throw new ServiceException('Source and destination must be different');
            }

            $items = $data['items'] ?? [];
            unset($data['items']);

            $data['status'] = $data['status'] ?? 'draft';

            $transfer = $this->repository->create($data);

            if (!empty($items)) {
                $this->createTransferItems($transfer, $items);
            }

            $this->calculateTotalCost($transfer);

            DB::commit();
            
            return $this->repository->getWithItems($transfer->id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create warehouse transfer: ' . $e->getMessage());
            throw new ServiceException('Failed to create warehouse transfer: ' . $e->getMessage());
        }
    }

    public function update(int $id, array $data)
    {
        DB::beginTransaction();
        
        try {
            $transfer = $this->repository->findById($id);
            
            if (!$transfer) {
                throw new ServiceException('Warehouse transfer not found');
            }

            if (!in_array($transfer->status, ['draft', 'pending'])) {
                throw new ServiceException('Cannot update transfer in current status');
            }

            if (isset($data['transfer_number']) && $data['transfer_number'] !== $transfer->transfer_number) {
                $existing = $this->repository->findByNumber($data['transfer_number']);
                if ($existing && $existing->id !== $id) {
                    throw new ServiceException('Transfer number already exists');
                }
            }

            $items = $data['items'] ?? null;
            unset($data['items']);

            $this->repository->update($id, $data);

            if ($items !== null) {
                $transfer->items()->delete();
                $this->createTransferItems($transfer, $items);
            }

            $this->calculateTotalCost($transfer);

            DB::commit();
            
            return $this->repository->getWithItems($id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update warehouse transfer: ' . $e->getMessage());
            throw new ServiceException('Failed to update warehouse transfer: ' . $e->getMessage());
        }
    }

    public function approve(int $id)
    {
        DB::beginTransaction();
        
        try {
            $transfer = $this->repository->findById($id);
            
            if (!$transfer) {
                throw new ServiceException('Warehouse transfer not found');
            }

            if (!$transfer->canApprove()) {
                throw new ServiceException('Transfer cannot be approved in current status');
            }

            if ($transfer->items->isEmpty()) {
                throw new ServiceException('Transfer must have at least one item');
            }

            $this->repository->update($id, [
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            DB::commit();
            
            return $this->repository->getWithItems($id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to approve warehouse transfer: ' . $e->getMessage());
            throw new ServiceException('Failed to approve warehouse transfer: ' . $e->getMessage());
        }
    }

    public function ship(int $id, array $data = [])
    {
        DB::beginTransaction();
        
        try {
            $transfer = $this->repository->getWithItems($id);
            
            if (!$transfer) {
                throw new ServiceException('Warehouse transfer not found');
            }

            if (!$transfer->canShip()) {
                throw new ServiceException('Transfer cannot be shipped in current status');
            }

            foreach ($transfer->items as $item) {
                $this->validateStockAvailability(
                    $item->product_id,
                    $item->quantity_requested,
                    $transfer->source_branch_id,
                    $transfer->source_location_id,
                    $item->variant_id
                );

                $this->inventoryService->stockOut([
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'branch_id' => $transfer->source_branch_id,
                    'location_id' => $transfer->source_location_id,
                    'quantity' => $item->quantity_requested,
                    'unit_id' => $item->unit_id,
                    'transaction_type' => 'transfer_out',
                    'reference_type' => 'WarehouseTransfer',
                    'reference_id' => $transfer->id,
                    'reference_number' => $transfer->transfer_number,
                    'transaction_date' => now(),
                    'batch_number' => $item->batch_number,
                    'serial_number' => $item->serial_number,
                    'lot_number' => $item->lot_number,
                    'expiry_date' => $item->expiry_date,
                    'unit_cost' => $item->unit_cost,
                    'total_cost' => $item->total_cost,
                ]);

                $item->update(['quantity_shipped' => $item->quantity_requested]);
            }

            $updateData = [
                'status' => 'in_transit',
            ];

            if (isset($data['tracking_number'])) {
                $updateData['tracking_number'] = $data['tracking_number'];
            }
            if (isset($data['carrier'])) {
                $updateData['carrier'] = $data['carrier'];
            }

            $this->repository->update($id, $updateData);

            DB::commit();
            
            return $this->repository->getWithItems($id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to ship warehouse transfer: ' . $e->getMessage());
            throw new ServiceException('Failed to ship warehouse transfer: ' . $e->getMessage());
        }
    }

    public function receive(int $id, array $itemQuantities = [])
    {
        DB::beginTransaction();
        
        try {
            $transfer = $this->repository->getWithItems($id);
            
            if (!$transfer) {
                throw new ServiceException('Warehouse transfer not found');
            }

            if (!$transfer->canReceive()) {
                throw new ServiceException('Transfer cannot be received in current status');
            }

            foreach ($transfer->items as $item) {
                $receivedQty = $itemQuantities[$item->id] ?? $item->quantity_shipped;

                $this->inventoryService->stockIn([
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'branch_id' => $transfer->destination_branch_id,
                    'location_id' => $transfer->destination_location_id,
                    'quantity' => $receivedQty,
                    'unit_id' => $item->unit_id,
                    'transaction_type' => 'transfer_in',
                    'reference_type' => 'WarehouseTransfer',
                    'reference_id' => $transfer->id,
                    'reference_number' => $transfer->transfer_number,
                    'transaction_date' => now(),
                    'batch_number' => $item->batch_number,
                    'serial_number' => $item->serial_number,
                    'lot_number' => $item->lot_number,
                    'expiry_date' => $item->expiry_date,
                    'unit_cost' => $item->unit_cost,
                    'total_cost' => $receivedQty * $item->unit_cost,
                ]);

                $item->update(['quantity_received' => $receivedQty]);
            }

            $this->repository->update($id, [
                'status' => 'received',
                'actual_delivery_date' => now(),
            ]);

            DB::commit();
            
            return $this->repository->getWithItems($id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to receive warehouse transfer: ' . $e->getMessage());
            throw new ServiceException('Failed to receive warehouse transfer: ' . $e->getMessage());
        }
    }

    public function cancel(int $id, string $reason)
    {
        DB::beginTransaction();
        
        try {
            $transfer = $this->repository->findById($id);
            
            if (!$transfer) {
                throw new ServiceException('Warehouse transfer not found');
            }

            if (!$transfer->canCancel()) {
                throw new ServiceException('Transfer cannot be cancelled in current status');
            }

            $this->repository->update($id, [
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_by' => auth()->id(),
                'cancelled_at' => now(),
            ]);

            DB::commit();
            
            return $this->repository->getWithItems($id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to cancel warehouse transfer: ' . $e->getMessage());
            throw new ServiceException('Failed to cancel warehouse transfer: ' . $e->getMessage());
        }
    }

    protected function createTransferItems(WarehouseTransfer $transfer, array $items): void
    {
        foreach ($items as $item) {
            $item['warehouse_transfer_id'] = $transfer->id;
            $item['quantity_shipped'] = 0;
            $item['quantity_received'] = 0;
            
            if (isset($item['unit_cost']) && isset($item['quantity_requested'])) {
                $item['total_cost'] = $item['unit_cost'] * $item['quantity_requested'];
            }
            
            WarehouseTransferItem::create($item);
        }
    }

    protected function calculateTotalCost(WarehouseTransfer $transfer): void
    {
        $itemsCost = $transfer->items->sum('total_cost');
        $totalCost = $itemsCost + $transfer->shipping_cost + $transfer->handling_cost;
        
        $this->repository->update($transfer->id, ['total_cost' => $totalCost]);
    }

    protected function validateStockAvailability(
        int $productId,
        float $quantity,
        int $branchId,
        ?int $locationId,
        ?int $variantId
    ): void {
        $currentBalance = $this->inventoryService->getCurrentBalance(
            $productId,
            $branchId,
            $locationId,
            $variantId
        );

        if ($currentBalance < $quantity) {
            throw new ServiceException(
                "Insufficient stock. Available: {$currentBalance}, Required: {$quantity}"
            );
        }
    }

    protected function generateTransferNumber(): string
    {
        $prefix = 'TRF';
        $date = now()->format('Ymd');
        $count = WarehouseTransfer::whereDate('created_at', today())->count() + 1;
        
        return sprintf('%s-%s-%04d', $prefix, $date, $count);
    }
}
