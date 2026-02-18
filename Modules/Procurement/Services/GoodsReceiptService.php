<?php

declare(strict_types=1);

namespace Modules\Procurement\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Services\BaseService;
use Modules\Procurement\Entities\GoodsReceipt;
use Modules\Procurement\Repositories\Contracts\GoodsReceiptRepositoryInterface;
use Modules\Procurement\Repositories\Contracts\PurchaseOrderRepositoryInterface;

/**
 * Goods Receipt Service
 *
 * Handles business logic for receipt processing and 3-way matching.
 */
class GoodsReceiptService extends BaseService
{
    /**
     * GoodsReceiptService constructor.
     */
    public function __construct(
        protected GoodsReceiptRepositoryInterface $goodsReceiptRepository,
        protected PurchaseOrderRepositoryInterface $purchaseOrderRepository
    ) {}

    /**
     * Get paginated goods receipts.
     */
    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->goodsReceiptRepository->paginate($perPage);
    }

    /**
     * Create a new goods receipt.
     */
    public function create(array $data): GoodsReceipt
    {
        return $this->executeInTransaction(function () use ($data) {
            // Generate receipt number if not provided
            if (empty($data['receipt_number'])) {
                $data['receipt_number'] = $this->generateReceiptNumber();
            }

            // Set default status if not provided
            if (empty($data['status'])) {
                $data['status'] = 'draft';
            }

            // Set default matched status if not provided
            if (empty($data['matched_status'])) {
                $data['matched_status'] = 'unmatched';
            }

            $goodsReceipt = $this->goodsReceiptRepository->create($data);

            $this->logInfo('Goods receipt created', [
                'goods_receipt_id' => $goodsReceipt->id,
                'receipt_number' => $goodsReceipt->receipt_number,
            ]);

            return $goodsReceipt;
        });
    }

    /**
     * Create goods receipt from purchase order.
     */
    public function createFromPurchaseOrder(
        int $purchaseOrderId,
        array $receivedQuantities,
        array $additionalData = []
    ): GoodsReceipt {
        return $this->executeInTransaction(function () use ($purchaseOrderId, $receivedQuantities, $additionalData) {
            $purchaseOrder = $this->purchaseOrderRepository->findWithLines($purchaseOrderId);

            if (! $purchaseOrder) {
                throw new \Exception("Purchase order with ID {$purchaseOrderId} not found.");
            }

            // Create goods receipt
            $receiptData = array_merge([
                'purchase_order_id' => $purchaseOrder->id,
                'received_date' => now(),
            ], $additionalData);

            $goodsReceipt = $this->create($receiptData);

            // Update received quantities in purchase order lines
            foreach ($receivedQuantities as $lineId => $quantity) {
                $line = $purchaseOrder->lines->find($lineId);

                if ($line) {
                    $line->updateReceivedQuantity($quantity);
                }
            }

            $this->logInfo('Goods receipt created from purchase order', [
                'goods_receipt_id' => $goodsReceipt->id,
                'purchase_order_id' => $purchaseOrder->id,
            ]);

            return $goodsReceipt->fresh();
        });
    }

    /**
     * Update an existing goods receipt.
     */
    public function update(int $id, array $data): GoodsReceipt
    {
        return $this->executeInTransaction(function () use ($id, $data) {
            $goodsReceipt = $this->goodsReceiptRepository->update($id, $data);

            $this->logInfo('Goods receipt updated', [
                'goods_receipt_id' => $goodsReceipt->id,
            ]);

            return $goodsReceipt;
        });
    }

    /**
     * Delete a goods receipt.
     */
    public function delete(int $id): bool
    {
        return $this->executeInTransaction(function () use ($id) {
            $result = $this->goodsReceiptRepository->delete($id);

            if ($result) {
                $this->logInfo('Goods receipt deleted', [
                    'goods_receipt_id' => $id,
                ]);
            }

            return $result;
        });
    }

    /**
     * Find goods receipt by ID.
     */
    public function findById(int $id): ?GoodsReceipt
    {
        return $this->goodsReceiptRepository->findById($id);
    }

    /**
     * Confirm a goods receipt.
     */
    public function confirm(int $id): GoodsReceipt
    {
        return $this->executeInTransaction(function () use ($id) {
            $goodsReceipt = $this->goodsReceiptRepository->findById($id);

            if (! $goodsReceipt) {
                throw new \Exception("Goods receipt with ID {$id} not found.");
            }

            if ($goodsReceipt->status === 'confirmed') {
                throw new \Exception('Goods receipt is already confirmed.');
            }

            $goodsReceipt->confirm();

            $this->logInfo('Goods receipt confirmed', [
                'goods_receipt_id' => $goodsReceipt->id,
                'receipt_number' => $goodsReceipt->receipt_number,
            ]);

            return $goodsReceipt;
        });
    }

    /**
     * Perform 3-way matching.
     */
    public function performThreeWayMatch(int $id): bool
    {
        return $this->executeInTransaction(function () use ($id) {
            $goodsReceipt = $this->goodsReceiptRepository->findById($id);

            if (! $goodsReceipt) {
                throw new \Exception("Goods receipt with ID {$id} not found.");
            }

            $matched = $goodsReceipt->performThreeWayMatch();

            $this->logInfo('3-way matching performed', [
                'goods_receipt_id' => $goodsReceipt->id,
                'matched' => $matched,
            ]);

            return $matched;
        });
    }

    /**
     * Get receipts by purchase order.
     */
    public function getReceiptsByPurchaseOrder(int $purchaseOrderId): Collection
    {
        return $this->goodsReceiptRepository->getReceiptsByPurchaseOrder($purchaseOrderId);
    }

    /**
     * Get receipts by status.
     */
    public function getReceiptsByStatus(string $status): Collection
    {
        return $this->goodsReceiptRepository->getReceiptsByStatus($status);
    }

    /**
     * Search goods receipts.
     */
    public function search(string $query): Collection
    {
        return $this->goodsReceiptRepository->search($query);
    }

    /**
     * Generate a unique receipt number.
     */
    protected function generateReceiptNumber(): string
    {
        $prefix = 'GR';
        $year = date('Y');

        // Get the last receipt number for this year
        $lastReceipt = $this->goodsReceiptRepository
            ->getModel()
            ->where('receipt_number', 'LIKE', "{$prefix}-{$year}-%")
            ->orderBy('receipt_number', 'desc')
            ->first();

        if ($lastReceipt) {
            // Extract the sequence number and increment
            $parts = explode('-', $lastReceipt->receipt_number);
            $sequence = (int) end($parts) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s-%s-%05d', $prefix, $year, $sequence);
    }
}
