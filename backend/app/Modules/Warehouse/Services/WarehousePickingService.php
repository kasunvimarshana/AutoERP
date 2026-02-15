<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Services;

use App\Core\Services\BaseService;
use App\Core\Exceptions\ServiceException;
use App\Modules\Warehouse\Repositories\WarehousePickingRepositoryInterface;
use App\Modules\Warehouse\Models\WarehousePicking;
use App\Modules\Warehouse\Models\WarehousePickingItem;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WarehousePickingService extends BaseService
{
    protected InventoryService $inventoryService;

    public function __construct(
        WarehousePickingRepositoryInterface $repository,
        InventoryService $inventoryService
    ) {
        parent::__construct($repository);
        $this->inventoryService = $inventoryService;
    }

    public function create(array $data)
    {
        DB::beginTransaction();
        
        try {
            if (isset($data['picking_number'])) {
                $existing = $this->repository->findByNumber($data['picking_number']);
                if ($existing) {
                    throw new ServiceException('Picking number already exists');
                }
            } else {
                $data['picking_number'] = $this->generatePickingNumber();
            }

            $items = $data['items'] ?? [];
            unset($data['items']);

            $data['status'] = $data['status'] ?? 'pending';

            $picking = $this->repository->create($data);

            if (!empty($items)) {
                $this->createPickingItems($picking, $items);
            }

            DB::commit();
            
            return $this->repository->getWithItems($picking->id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create warehouse picking: ' . $e->getMessage());
            throw new ServiceException('Failed to create warehouse picking: ' . $e->getMessage());
        }
    }

    public function assign(int $id, int $userId)
    {
        DB::beginTransaction();
        
        try {
            $picking = $this->repository->findById($id);
            
            if (!$picking) {
                throw new ServiceException('Warehouse picking not found');
            }

            if (!$picking->canAssign()) {
                throw new ServiceException('Picking cannot be assigned in current status');
            }

            $this->repository->update($id, [
                'status' => 'assigned',
                'assigned_to' => $userId,
                'assigned_at' => now(),
            ]);

            DB::commit();
            
            return $this->repository->getWithItems($id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to assign warehouse picking: ' . $e->getMessage());
            throw new ServiceException('Failed to assign warehouse picking: ' . $e->getMessage());
        }
    }

    public function start(int $id)
    {
        DB::beginTransaction();
        
        try {
            $picking = $this->repository->findById($id);
            
            if (!$picking) {
                throw new ServiceException('Warehouse picking not found');
            }

            if (!$picking->canStart()) {
                throw new ServiceException('Picking cannot be started in current status');
            }

            $this->repository->update($id, [
                'status' => 'in_progress',
                'started_at' => now(),
            ]);

            DB::commit();
            
            return $this->repository->getWithItems($id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to start warehouse picking: ' . $e->getMessage());
            throw new ServiceException('Failed to start warehouse picking: ' . $e->getMessage());
        }
    }

    public function pick(int $id, array $itemQuantities)
    {
        DB::beginTransaction();
        
        try {
            $picking = $this->repository->getWithItems($id);
            
            if (!$picking) {
                throw new ServiceException('Warehouse picking not found');
            }

            if ($picking->status !== 'in_progress') {
                throw new ServiceException('Picking must be in progress to record picks');
            }

            foreach ($itemQuantities as $itemId => $quantity) {
                $item = $picking->items->find($itemId);
                
                if (!$item) {
                    continue;
                }

                $pickedQty = min($quantity, $item->quantity_required);
                
                $item->update([
                    'quantity_picked' => $pickedQty,
                    'status' => $pickedQty >= $item->quantity_required ? 'completed' : 'short',
                ]);

                $this->inventoryService->stockOut([
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'branch_id' => $picking->branch_id,
                    'location_id' => $item->location_id,
                    'quantity' => $pickedQty,
                    'unit_id' => $item->unit_id,
                    'transaction_type' => 'sale',
                    'reference_type' => $picking->reference_type ?? 'WarehousePicking',
                    'reference_id' => $picking->reference_id ?? $picking->id,
                    'reference_number' => $picking->reference_number ?? $picking->picking_number,
                    'transaction_date' => now(),
                    'batch_number' => $item->batch_number,
                    'serial_number' => $item->serial_number,
                    'lot_number' => $item->lot_number,
                ]);
            }

            DB::commit();
            
            return $this->repository->getWithItems($id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to record picks: ' . $e->getMessage());
            throw new ServiceException('Failed to record picks: ' . $e->getMessage());
        }
    }

    public function complete(int $id)
    {
        DB::beginTransaction();
        
        try {
            $picking = $this->repository->findById($id);
            
            if (!$picking) {
                throw new ServiceException('Warehouse picking not found');
            }

            if (!$picking->canComplete()) {
                throw new ServiceException('Picking cannot be completed in current status');
            }

            $this->repository->update($id, [
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            DB::commit();
            
            return $this->repository->getWithItems($id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to complete warehouse picking: ' . $e->getMessage());
            throw new ServiceException('Failed to complete warehouse picking: ' . $e->getMessage());
        }
    }

    public function cancel(int $id, string $reason)
    {
        DB::beginTransaction();
        
        try {
            $picking = $this->repository->findById($id);
            
            if (!$picking) {
                throw new ServiceException('Warehouse picking not found');
            }

            if (!$picking->canCancel()) {
                throw new ServiceException('Picking cannot be cancelled in current status');
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
            Log::error('Failed to cancel warehouse picking: ' . $e->getMessage());
            throw new ServiceException('Failed to cancel warehouse picking: ' . $e->getMessage());
        }
    }

    protected function createPickingItems(WarehousePicking $picking, array $items): void
    {
        foreach ($items as $index => $item) {
            $item['warehouse_picking_id'] = $picking->id;
            $item['quantity_picked'] = 0;
            $item['status'] = 'pending';
            $item['sequence'] = $item['sequence'] ?? $index + 1;
            
            WarehousePickingItem::create($item);
        }
    }

    protected function generatePickingNumber(): string
    {
        $prefix = 'PICK';
        $date = now()->format('Ymd');
        $count = WarehousePicking::whereDate('created_at', today())->count() + 1;
        
        return sprintf('%s-%s-%04d', $prefix, $date, $count);
    }
}
