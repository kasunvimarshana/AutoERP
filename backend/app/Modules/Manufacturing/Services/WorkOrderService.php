<?php

declare(strict_types=1);

namespace App\Modules\Manufacturing\Services;

use App\Core\Services\BaseService;
use App\Core\Exceptions\ServiceException;
use App\Modules\Manufacturing\Repositories\WorkOrderRepositoryInterface;
use App\Modules\Manufacturing\Repositories\BillOfMaterialRepositoryInterface;
use App\Modules\Manufacturing\Models\WorkOrder;
use App\Modules\Manufacturing\Models\WorkOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * WorkOrderService
 * 
 * Handles business logic for Work Order management and production operations
 */
class WorkOrderService extends BaseService
{
    protected BillOfMaterialRepositoryInterface $bomRepository;

    /**
     * WorkOrderService constructor.
     *
     * @param WorkOrderRepositoryInterface $repository
     * @param BillOfMaterialRepositoryInterface $bomRepository
     */
    public function __construct(
        WorkOrderRepositoryInterface $repository,
        BillOfMaterialRepositoryInterface $bomRepository
    ) {
        parent::__construct($repository);
        $this->bomRepository = $bomRepository;
    }

    /**
     * Create a new work order
     *
     * @param array $data
     * @return WorkOrder
     * @throws ServiceException
     */
    public function create(array $data)
    {
        DB::beginTransaction();
        
        try {
            // Check if work order number already exists
            if (isset($data['work_order_number'])) {
                $existing = $this->repository->findByNumber($data['work_order_number']);
                if ($existing) {
                    throw new ServiceException('Work order number already exists');
                }
            } else {
                // Auto-generate work order number if not provided
                $data['work_order_number'] = $this->generateWorkOrderNumber();
            }

            // If BOM is not specified, use default BOM for the product
            if (!isset($data['bom_id']) && isset($data['product_id'])) {
                $defaultBOM = $this->bomRepository->getDefaultBOM($data['product_id']);
                if ($defaultBOM) {
                    $data['bom_id'] = $defaultBOM->id;
                }
            }

            // Extract items data (if provided)
            $items = $data['items'] ?? null;
            unset($data['items']);

            // Set default status if not provided
            $data['status'] = $data['status'] ?? 'draft';

            // Create the work order
            $workOrder = $this->repository->create($data);

            // Create work order items from BOM or provided items
            if ($items !== null) {
                $this->createWorkOrderItems($workOrder, $items);
            } elseif ($workOrder->bom_id) {
                $this->createWorkOrderItemsFromBOM($workOrder);
            }

            // Calculate estimated cost
            $this->updateEstimatedCost($workOrder);

            DB::commit();
            
            return $this->repository->getWithItems($workOrder->id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create work order: ' . $e->getMessage());
            throw new ServiceException('Failed to create work order: ' . $e->getMessage());
        }
    }

    /**
     * Update work order
     *
     * @param int $id
     * @param array $data
     * @return WorkOrder
     * @throws ServiceException
     */
    public function update(int $id, array $data)
    {
        DB::beginTransaction();
        
        try {
            $workOrder = $this->repository->findById($id);
            
            if (!$workOrder) {
                throw new ServiceException('Work order not found');
            }

            // Validate status transitions
            if (isset($data['status']) && !$this->canChangeStatus($workOrder, $data['status'])) {
                throw new ServiceException("Cannot change status from {$workOrder->status} to {$data['status']}");
            }

            // Check work order number uniqueness if changed
            if (isset($data['work_order_number']) && $data['work_order_number'] !== $workOrder->work_order_number) {
                $existing = $this->repository->findByNumber($data['work_order_number']);
                if ($existing && $existing->id !== $id) {
                    throw new ServiceException('Work order number already exists');
                }
            }

            // Extract items data
            $items = $data['items'] ?? null;
            unset($data['items']);

            // Update the work order
            $this->repository->update($id, $data);

            // Update work order items if provided
            if ($items !== null) {
                $this->updateWorkOrderItems($workOrder, $items);
            }

            // Recalculate costs if needed
            if ($items !== null || isset($data['planned_quantity'])) {
                $this->updateEstimatedCost($workOrder);
            }

            DB::commit();
            
            return $this->repository->getWithItems($id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update work order: ' . $e->getMessage());
            throw new ServiceException('Failed to update work order: ' . $e->getMessage());
        }
    }

    /**
     * Start production (move to in_progress status)
     *
     * @param int $id
     * @return WorkOrder
     * @throws ServiceException
     */
    public function startProduction(int $id)
    {
        DB::beginTransaction();
        
        try {
            $workOrder = $this->repository->findById($id);
            
            if (!$workOrder) {
                throw new ServiceException('Work order not found');
            }

            if (!$workOrder->canStart()) {
                throw new ServiceException('Work order cannot be started from current status');
            }

            // Validate material availability (optional - can be implemented later)
            // $this->validateMaterialAvailability($workOrder);

            $this->repository->update($id, [
                'status' => 'in_progress',
                'actual_start_date' => now(),
            ]);

            DB::commit();
            
            return $this->repository->findById($id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to start production: ' . $e->getMessage());
            throw new ServiceException('Failed to start production: ' . $e->getMessage());
        }
    }

    /**
     * Complete production
     *
     * @param int $id
     * @param array $data
     * @return WorkOrder
     * @throws ServiceException
     */
    public function completeProduction(int $id, array $data = [])
    {
        DB::beginTransaction();
        
        try {
            $workOrder = $this->repository->findById($id);
            
            if (!$workOrder) {
                throw new ServiceException('Work order not found');
            }

            if (!$workOrder->canComplete()) {
                throw new ServiceException('Work order cannot be completed from current status');
            }

            $updateData = [
                'status' => 'completed',
                'actual_end_date' => now(),
            ];

            if (isset($data['produced_quantity'])) {
                $updateData['produced_quantity'] = $data['produced_quantity'];
            }

            if (isset($data['scrap_quantity'])) {
                $updateData['scrap_quantity'] = $data['scrap_quantity'];
            }

            if (isset($data['actual_cost'])) {
                $updateData['actual_cost'] = $data['actual_cost'];
            }

            $this->repository->update($id, $updateData);

            // Trigger inventory updates (to be implemented with Inventory Service integration)
            // $this->updateInventory($workOrder);

            DB::commit();
            
            return $this->repository->findById($id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to complete production: ' . $e->getMessage());
            throw new ServiceException('Failed to complete production: ' . $e->getMessage());
        }
    }

    /**
     * Cancel work order
     *
     * @param int $id
     * @param string $reason
     * @return WorkOrder
     * @throws ServiceException
     */
    public function cancel(int $id, string $reason = '')
    {
        DB::beginTransaction();
        
        try {
            $workOrder = $this->repository->findById($id);
            
            if (!$workOrder) {
                throw new ServiceException('Work order not found');
            }

            if (!$workOrder->canCancel()) {
                throw new ServiceException('Work order cannot be cancelled from current status');
            }

            $this->repository->update($id, [
                'status' => 'cancelled',
                'cancelled_by' => auth()->id(),
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            // Release allocated materials (to be implemented with Inventory Service integration)
            // $this->releaseAllocatedMaterials($workOrder);

            DB::commit();
            
            return $this->repository->findById($id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to cancel work order: ' . $e->getMessage());
            throw new ServiceException('Failed to cancel work order: ' . $e->getMessage());
        }
    }

    /**
     * Get work orders by status
     *
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByStatus(string $status)
    {
        return $this->repository->getByStatus($status);
    }

    /**
     * Get in-progress work orders
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getInProgress()
    {
        return $this->repository->getInProgress();
    }

    /**
     * Get overdue work orders
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOverdue()
    {
        return $this->repository->getOverdue();
    }

    /**
     * Search work orders
     *
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function search(array $filters = [])
    {
        return $this->repository->search($filters);
    }

    /**
     * Generate unique work order number
     *
     * @return string
     */
    protected function generateWorkOrderNumber(): string
    {
        $prefix = 'WO';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(md5(uniqid()), 0, 6));
        
        return "{$prefix}-{$date}-{$random}";
    }

    /**
     * Create work order items from BOM
     *
     * @param WorkOrder $workOrder
     * @return void
     */
    protected function createWorkOrderItemsFromBOM(WorkOrder $workOrder): void
    {
        $bom = $this->bomRepository->getWithItems($workOrder->bom_id);
        
        if (!$bom) {
            return;
        }

        $quantity = $workOrder->planned_quantity;

        foreach ($bom->items as $index => $bomItem) {
            $plannedQty = $bomItem->required_quantity * $quantity;
            
            WorkOrderItem::create([
                'work_order_id' => $workOrder->id,
                'product_id' => $bomItem->product_id,
                'bom_item_id' => $bomItem->id,
                'planned_quantity' => $plannedQty,
                'unit_id' => $bomItem->unit_id,
                'unit_cost' => $bomItem->unit_cost,
                'total_cost' => $plannedQty * $bomItem->unit_cost,
                'scrap_percentage' => $bomItem->scrap_percentage,
                'sequence' => $bomItem->sequence,
                'status' => 'pending',
            ]);
        }
    }

    /**
     * Create work order items
     *
     * @param WorkOrder $workOrder
     * @param array $items
     * @return void
     */
    protected function createWorkOrderItems(WorkOrder $workOrder, array $items): void
    {
        foreach ($items as $index => $itemData) {
            $itemData['work_order_id'] = $workOrder->id;
            $itemData['sequence'] = $itemData['sequence'] ?? $index + 1;
            $itemData['status'] = $itemData['status'] ?? 'pending';
            $itemData['total_cost'] = ($itemData['planned_quantity'] ?? 0) * ($itemData['unit_cost'] ?? 0);
            
            WorkOrderItem::create($itemData);
        }
    }

    /**
     * Update work order items
     *
     * @param WorkOrder $workOrder
     * @param array $items
     * @return void
     */
    protected function updateWorkOrderItems(WorkOrder $workOrder, array $items): void
    {
        // Delete existing items
        $workOrder->items()->delete();
        
        // Create new items
        $this->createWorkOrderItems($workOrder, $items);
    }

    /**
     * Update estimated cost of work order
     *
     * @param WorkOrder $workOrder
     * @return void
     */
    protected function updateEstimatedCost(WorkOrder $workOrder): void
    {
        $workOrder->refresh();
        $materialCost = $workOrder->items->sum('total_cost');
        $estimatedCost = $materialCost + ($workOrder->labor_cost ?? 0) + ($workOrder->overhead_cost ?? 0);
        
        $workOrder->update([
            'material_cost' => $materialCost,
            'estimated_cost' => $estimatedCost,
        ]);
    }

    /**
     * Check if status change is allowed
     *
     * @param WorkOrder $workOrder
     * @param string $newStatus
     * @return bool
     */
    protected function canChangeStatus(WorkOrder $workOrder, string $newStatus): bool
    {
        $allowedTransitions = [
            'draft' => ['planned', 'cancelled'],
            'planned' => ['released', 'cancelled'],
            'released' => ['in_progress', 'cancelled'],
            'in_progress' => ['completed', 'cancelled'],
            'completed' => [],
            'cancelled' => [],
        ];

        $currentStatus = $workOrder->status;
        
        return in_array($newStatus, $allowedTransitions[$currentStatus] ?? []);
    }
}
