<?php

namespace App\Modules\InventoryManagement\Services;

use App\Core\Base\BaseService;
use App\Modules\InventoryManagement\Events\PurchaseOrderCreated;
use App\Modules\InventoryManagement\Events\PurchaseOrderApproved;
use App\Modules\InventoryManagement\Events\PurchaseOrderReceived;
use App\Modules\InventoryManagement\Repositories\PurchaseOrderRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService extends BaseService
{
    public function __construct(PurchaseOrderRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * After purchase order creation hook
     */
    protected function afterCreate(Model $purchaseOrder, array $data): void
    {
        $purchaseOrder->po_number = $this->generatePONumber($purchaseOrder->id);
        $purchaseOrder->save();
        
        event(new PurchaseOrderCreated($purchaseOrder));
    }

    /**
     * Approve a purchase order
     */
    public function approve(int $purchaseOrderId, int $approvedBy): Model
    {
        try {
            DB::beginTransaction();

            $purchaseOrder = $this->repository->findOrFail($purchaseOrderId);
            $purchaseOrder->status = 'approved';
            $purchaseOrder->approved_at = now();
            $purchaseOrder->approved_by = $approvedBy;
            $purchaseOrder->save();

            event(new PurchaseOrderApproved($purchaseOrder));

            DB::commit();

            return $purchaseOrder;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reject a purchase order
     */
    public function reject(int $purchaseOrderId, string $reason): Model
    {
        return $this->update($purchaseOrderId, [
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'rejected_at' => now()
        ]);
    }

    /**
     * Mark purchase order as received
     */
    public function receive(int $purchaseOrderId, array $receivedData = []): Model
    {
        try {
            DB::beginTransaction();

            $purchaseOrder = $this->repository->findOrFail($purchaseOrderId);
            $purchaseOrder->status = 'received';
            $purchaseOrder->received_at = $receivedData['received_at'] ?? now();
            $purchaseOrder->received_by = $receivedData['received_by'] ?? null;
            
            if (isset($receivedData['notes'])) {
                $purchaseOrder->receiving_notes = $receivedData['notes'];
            }

            $purchaseOrder->save();

            event(new PurchaseOrderReceived($purchaseOrder));

            DB::commit();

            return $purchaseOrder;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cancel a purchase order
     */
    public function cancel(int $purchaseOrderId, ?string $reason = null): Model
    {
        return $this->update($purchaseOrderId, [
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_at' => now()
        ]);
    }

    /**
     * Get purchase orders by status
     */
    public function getByStatus(string $status)
    {
        return $this->repository->getByStatus($status);
    }

    /**
     * Get purchase orders by supplier
     */
    public function getBySupplier(int $supplierId)
    {
        return $this->repository->getBySupplier($supplierId);
    }

    /**
     * Get pending purchase orders
     */
    public function getPending()
    {
        return $this->repository->getPending();
    }

    /**
     * Calculate total amount
     */
    public function calculateTotal(int $purchaseOrderId): float
    {
        $purchaseOrder = $this->repository->findOrFail($purchaseOrderId);
        return $purchaseOrder->items->sum(function ($item) {
            return $item->quantity * $item->unit_price;
        });
    }

    /**
     * Generate purchase order number
     */
    protected function generatePONumber(int $id): string
    {
        $prefix = 'PO';
        $date = date('Ymd');
        $number = str_pad($id, 5, '0', STR_PAD_LEFT);
        
        return "{$prefix}-{$date}-{$number}";
    }
}
