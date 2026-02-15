<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Services;

use App\Core\Services\BaseService;
use App\Core\Exceptions\ServiceException;
use App\Modules\Warehouse\Repositories\WarehousePutawayRepositoryInterface;
use App\Modules\Warehouse\Models\WarehousePutaway;
use App\Modules\Warehouse\Models\WarehousePutawayItem;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WarehousePutawayService extends BaseService
{
    protected InventoryService $inventoryService;

    public function __construct(
        WarehousePutawayRepositoryInterface $repository,
        InventoryService $inventoryService
    ) {
        parent::__construct($repository);
        $this->inventoryService = $inventoryService;
    }

    public function create(array $data)
    {
        DB::beginTransaction();
        
        try {
            if (isset($data['putaway_number'])) {
                $existing = $this->repository->findByNumber($data['putaway_number']);
                if ($existing) {
                    throw new ServiceException('Putaway number already exists');
                }
            } else {
                $data['putaway_number'] = $this->generatePutawayNumber();
            }

            $items = $data['items'] ?? [];
            unset($data['items']);

            $data['status'] = $data['status'] ?? 'pending';

            $putaway = $this->repository->create($data);

            if (!empty($items)) {
                $this->createPutawayItems($putaway, $items);
            }

            DB::commit();
            
            return $this->repository->getWithItems($putaway->id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create warehouse putaway: ' . $e->getMessage());
            throw new ServiceException('Failed to create warehouse putaway: ' . $e->getMessage());
        }
    }

    public function assign(int $id, int $userId)
    {
        DB::beginTransaction();
        
        try {
            $putaway = $this->repository->findById($id);
            
            if (!$putaway) {
                throw new ServiceException('Warehouse putaway not found');
            }

            if (!$putaway->canAssign()) {
                throw new ServiceException('Putaway cannot be assigned in current status');
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
            Log::error('Failed to assign warehouse putaway: ' . $e->getMessage());
            throw new ServiceException('Failed to assign warehouse putaway: ' . $e->getMessage());
        }
    }

    public function start(int $id)
    {
        DB::beginTransaction();
        
        try {
            $putaway = $this->repository->findById($id);
            
            if (!$putaway) {
                throw new ServiceException('Warehouse putaway not found');
            }

            if (!$putaway->canStart()) {
                throw new ServiceException('Putaway cannot be started in current status');
            }

            $this->repository->update($id, [
                'status' => 'in_progress',
                'started_at' => now(),
            ]);

            DB::commit();
            
            return $this->repository->getWithItems($id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to start warehouse putaway: ' . $e->getMessage());
            throw new ServiceException('Failed to start warehouse putaway: ' . $e->getMessage());
        }
    }

    public function putaway(int $id, array $itemQuantities)
    {
        DB::beginTransaction();
        
        try {
            $putaway = $this->repository->getWithItems($id);
            
            if (!$putaway) {
                throw new ServiceException('Warehouse putaway not found');
            }

            if ($putaway->status !== 'in_progress') {
                throw new ServiceException('Putaway must be in progress to record putaways');
            }

            foreach ($itemQuantities as $itemId => $quantity) {
                $item = $putaway->items->find($itemId);
                
                if (!$item) {
                    continue;
                }

                $putawayQty = min($quantity, $item->quantity_to_putaway);
                
                $item->update([
                    'quantity_putaway' => $putawayQty,
                    'status' => $putawayQty >= $item->quantity_to_putaway ? 'completed' : 'in_progress',
                ]);

                $this->inventoryService->stockIn([
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'branch_id' => $putaway->branch_id,
                    'location_id' => $item->destination_location_id,
                    'quantity' => $putawayQty,
                    'unit_id' => $item->unit_id,
                    'transaction_type' => $putaway->putaway_type === 'purchase' ? 'purchase' : 'return',
                    'reference_type' => $putaway->reference_type ?? 'WarehousePutaway',
                    'reference_id' => $putaway->reference_id ?? $putaway->id,
                    'reference_number' => $putaway->reference_number ?? $putaway->putaway_number,
                    'transaction_date' => now(),
                    'batch_number' => $item->batch_number,
                    'serial_number' => $item->serial_number,
                    'lot_number' => $item->lot_number,
                    'manufacture_date' => $item->manufacture_date,
                    'expiry_date' => $item->expiry_date,
                    'unit_cost' => $item->unit_cost,
                    'total_cost' => $putawayQty * $item->unit_cost,
                ]);
            }

            DB::commit();
            
            return $this->repository->getWithItems($id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to record putaways: ' . $e->getMessage());
            throw new ServiceException('Failed to record putaways: ' . $e->getMessage());
        }
    }

    public function complete(int $id)
    {
        DB::beginTransaction();
        
        try {
            $putaway = $this->repository->findById($id);
            
            if (!$putaway) {
                throw new ServiceException('Warehouse putaway not found');
            }

            if (!$putaway->canComplete()) {
                throw new ServiceException('Putaway cannot be completed in current status');
            }

            $this->repository->update($id, [
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            DB::commit();
            
            return $this->repository->getWithItems($id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to complete warehouse putaway: ' . $e->getMessage());
            throw new ServiceException('Failed to complete warehouse putaway: ' . $e->getMessage());
        }
    }

    public function cancel(int $id, string $reason)
    {
        DB::beginTransaction();
        
        try {
            $putaway = $this->repository->findById($id);
            
            if (!$putaway) {
                throw new ServiceException('Warehouse putaway not found');
            }

            if (!$putaway->canCancel()) {
                throw new ServiceException('Putaway cannot be cancelled in current status');
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
            Log::error('Failed to cancel warehouse putaway: ' . $e->getMessage());
            throw new ServiceException('Failed to cancel warehouse putaway: ' . $e->getMessage());
        }
    }

    protected function createPutawayItems(WarehousePutaway $putaway, array $items): void
    {
        foreach ($items as $index => $item) {
            $item['warehouse_putaway_id'] = $putaway->id;
            $item['quantity_putaway'] = 0;
            $item['status'] = 'pending';
            $item['sequence'] = $item['sequence'] ?? $index + 1;
            
            if (isset($item['unit_cost']) && isset($item['quantity_to_putaway'])) {
                $item['total_cost'] = $item['unit_cost'] * $item['quantity_to_putaway'];
            }
            
            WarehousePutawayItem::create($item);
        }
    }

    protected function generatePutawayNumber(): string
    {
        $prefix = 'PWAY';
        $date = now()->format('Ymd');
        $count = WarehousePutaway::whereDate('created_at', today())->count() + 1;
        
        return sprintf('%s-%s-%04d', $prefix, $date, $count);
    }
}
