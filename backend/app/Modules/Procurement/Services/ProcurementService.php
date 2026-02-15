<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Services;

use App\Core\Services\BaseService;
use App\Modules\Procurement\Repositories\VendorRepositoryInterface;
use App\Modules\Procurement\Repositories\PurchaseOrderRepositoryInterface;
use App\Modules\Procurement\Repositories\PurchaseReceiptRepositoryInterface;
use App\Modules\Inventory\Services\InventoryService;
use App\Core\Exceptions\ServiceException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProcurementService extends BaseService
{
    /**
     * @var PurchaseOrderRepositoryInterface
     */
    protected $purchaseOrderRepository;

    /**
     * @var PurchaseReceiptRepositoryInterface
     */
    protected $purchaseReceiptRepository;

    /**
     * @var InventoryService
     */
    protected $inventoryService;

    /**
     * ProcurementService constructor.
     */
    public function __construct(
        VendorRepositoryInterface $vendorRepository,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        PurchaseReceiptRepositoryInterface $purchaseReceiptRepository,
        InventoryService $inventoryService
    ) {
        parent::__construct($vendorRepository);
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->purchaseReceiptRepository = $purchaseReceiptRepository;
        $this->inventoryService = $inventoryService;
    }

    /**
     * Create vendor with contacts
     */
    public function createVendor(array $data): mixed
    {
        DB::beginTransaction();

        try {
            // Generate vendor code if not provided
            if (empty($data['code'])) {
                $data['code'] = $this->generateVendorCode();
            }

            // Ensure code uniqueness
            if ($this->repository->findByCode($data['code'])) {
                throw new ServiceException('Vendor code already exists');
            }

            // Extract contacts from data
            $contacts = $data['contacts'] ?? [];
            unset($data['contacts']);

            // Create vendor
            $vendor = $this->repository->create($data);

            // Create contacts if provided
            if (!empty($contacts)) {
                foreach ($contacts as $contact) {
                    $contact['vendor_id'] = $vendor->id;
                    $contact['tenant_id'] = $vendor->tenant_id;
                    $vendor->contacts()->create($contact);
                }
            }

            DB::commit();

            return $vendor->load(['contacts']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to create vendor: ' . $e->getMessage());
        }
    }

    /**
     * Update vendor
     */
    public function updateVendor(int $id, array $data): mixed
    {
        DB::beginTransaction();

        try {
            $vendor = $this->repository->findById($id);

            if (!$vendor) {
                throw new ServiceException('Vendor not found');
            }

            // Ensure code uniqueness if changed
            if (!empty($data['code']) && $data['code'] !== $vendor->code) {
                $existing = $this->repository->findByCode($data['code']);
                if ($existing && $existing->id !== $id) {
                    throw new ServiceException('Vendor code already exists');
                }
            }

            // Extract contacts from data
            unset($data['contacts']);

            // Update vendor
            $this->repository->update($id, $data);

            DB::commit();

            return $this->repository->findById($id);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to update vendor: ' . $e->getMessage());
        }
    }

    /**
     * Create purchase order with items
     */
    public function createPurchaseOrder(array $data): mixed
    {
        DB::beginTransaction();

        try {
            // Generate PO code if not provided
            if (empty($data['code'])) {
                $data['code'] = $this->generatePurchaseOrderCode();
            }

            // Ensure code uniqueness
            if ($this->purchaseOrderRepository->findByCode($data['code'])) {
                throw new ServiceException('Purchase order code already exists');
            }

            // Extract items from data
            $items = $data['items'] ?? [];
            unset($data['items']);

            // Set default statuses
            $data['status'] = $data['status'] ?? 'draft';
            $data['approval_status'] = $data['approval_status'] ?? 'pending';
            $data['payment_status'] = $data['payment_status'] ?? 'unpaid';

            // Calculate totals
            $this->calculatePurchaseOrderTotals($data, $items);

            // Create purchase order
            $purchaseOrder = $this->purchaseOrderRepository->create($data);

            // Create items
            if (!empty($items)) {
                foreach ($items as $item) {
                    $item['tenant_id'] = $purchaseOrder->tenant_id;
                    $item['created_by'] = Auth::id();
                    $item['received_quantity'] = 0;
                    
                    // Calculate item line total
                    $this->calculateLineTotal($item);
                    
                    $purchaseOrder->items()->create($item);
                }
            }

            DB::commit();

            return $purchaseOrder->load(['vendor', 'items.product']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to create purchase order: ' . $e->getMessage());
        }
    }

    /**
     * Update purchase order
     */
    public function updatePurchaseOrder(int $id, array $data): mixed
    {
        DB::beginTransaction();

        try {
            $purchaseOrder = $this->purchaseOrderRepository->findById($id);

            if (!$purchaseOrder) {
                throw new ServiceException('Purchase order not found');
            }

            // Check if PO can be edited
            if ($purchaseOrder->status === 'completed' || $purchaseOrder->status === 'cancelled') {
                throw new ServiceException('Cannot update completed or cancelled purchase order');
            }

            // Extract items from data
            $items = $data['items'] ?? [];
            unset($data['items']);

            // Recalculate totals if items provided
            if (!empty($items)) {
                $this->calculatePurchaseOrderTotals($data, $items);
            }

            // Update purchase order
            $this->purchaseOrderRepository->update($id, $data);

            // Update items if provided
            if (!empty($items)) {
                // Delete existing items and recreate
                $purchaseOrder->items()->delete();
                
                foreach ($items as $item) {
                    $item['tenant_id'] = $purchaseOrder->tenant_id;
                    $item['created_by'] = Auth::id();
                    $this->calculateLineTotal($item);
                    $purchaseOrder->items()->create($item);
                }
            }

            DB::commit();

            return $this->purchaseOrderRepository->findById($id);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to update purchase order: ' . $e->getMessage());
        }
    }

    /**
     * Approve purchase order
     */
    public function approvePurchaseOrder(int $id): mixed
    {
        DB::beginTransaction();

        try {
            $purchaseOrder = $this->purchaseOrderRepository->findById($id);

            if (!$purchaseOrder) {
                throw new ServiceException('Purchase order not found');
            }

            if ($purchaseOrder->approval_status !== 'pending') {
                throw new ServiceException('Purchase order is not pending approval');
            }

            $this->purchaseOrderRepository->update($id, [
                'approval_status' => 'approved',
                'status' => 'pending',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            DB::commit();

            return $this->purchaseOrderRepository->findById($id);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to approve purchase order: ' . $e->getMessage());
        }
    }

    /**
     * Reject purchase order
     */
    public function rejectPurchaseOrder(int $id, string $reason = null): mixed
    {
        DB::beginTransaction();

        try {
            $purchaseOrder = $this->purchaseOrderRepository->findById($id);

            if (!$purchaseOrder) {
                throw new ServiceException('Purchase order not found');
            }

            if ($purchaseOrder->approval_status !== 'pending') {
                throw new ServiceException('Purchase order is not pending approval');
            }

            $this->purchaseOrderRepository->update($id, [
                'approval_status' => 'rejected',
                'status' => 'cancelled',
                'cancelled_by' => Auth::id(),
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            DB::commit();

            return $this->purchaseOrderRepository->findById($id);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to reject purchase order: ' . $e->getMessage());
        }
    }

    /**
     * Cancel purchase order
     */
    public function cancelPurchaseOrder(int $id, string $reason = null): mixed
    {
        DB::beginTransaction();

        try {
            $purchaseOrder = $this->purchaseOrderRepository->findById($id);

            if (!$purchaseOrder) {
                throw new ServiceException('Purchase order not found');
            }

            if ($purchaseOrder->status === 'completed') {
                throw new ServiceException('Cannot cancel completed purchase order');
            }

            if ($purchaseOrder->status === 'cancelled') {
                throw new ServiceException('Purchase order is already cancelled');
            }

            $this->purchaseOrderRepository->update($id, [
                'status' => 'cancelled',
                'cancelled_by' => Auth::id(),
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            DB::commit();

            return $this->purchaseOrderRepository->findById($id);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to cancel purchase order: ' . $e->getMessage());
        }
    }

    /**
     * Create purchase receipt (GRN) with items and auto stock-in
     */
    public function createPurchaseReceipt(array $data): mixed
    {
        DB::beginTransaction();

        try {
            // Validate purchase order
            $purchaseOrder = $this->purchaseOrderRepository->findById($data['purchase_order_id']);
            
            if (!$purchaseOrder) {
                throw new ServiceException('Purchase order not found');
            }

            if ($purchaseOrder->status !== 'pending') {
                throw new ServiceException('Purchase order is not in pending status');
            }

            // Generate receipt code if not provided
            if (empty($data['code'])) {
                $data['code'] = $this->generateReceiptCode();
            }

            // Extract items from data
            $items = $data['items'] ?? [];
            unset($data['items']);

            // Set default values
            $data['vendor_id'] = $purchaseOrder->vendor_id;
            $data['organization_id'] = $data['organization_id'] ?? $purchaseOrder->organization_id;
            $data['branch_id'] = $data['branch_id'] ?? $purchaseOrder->branch_id;
            $data['location_id'] = $data['location_id'] ?? $purchaseOrder->location_id;
            $data['status'] = $data['status'] ?? 'draft';
            $data['quality_check_status'] = $data['quality_check_status'] ?? 'pending';

            // Create purchase receipt
            $receipt = $this->purchaseReceiptRepository->create($data);

            // Process items and create stock ledger entries
            foreach ($items as $item) {
                $item['tenant_id'] = $receipt->tenant_id;
                $item['created_by'] = Auth::id();
                
                // Set accepted/rejected quantities
                $item['accepted_quantity'] = $item['accepted_quantity'] ?? $item['received_quantity'];
                $item['rejected_quantity'] = $item['rejected_quantity'] ?? 0;

                $receiptItem = $receipt->items()->create($item);

                // Update PO item received quantity
                if (isset($item['purchase_order_item_id'])) {
                    $poItem = $purchaseOrder->items()->find($item['purchase_order_item_id']);
                    if ($poItem) {
                        $poItem->received_quantity += $item['accepted_quantity'];
                        $poItem->save();
                    }
                }

                // Auto stock-in accepted quantity
                if ($item['accepted_quantity'] > 0 && ($item['quality_status'] ?? 'passed') === 'passed') {
                    $this->inventoryService->stockIn([
                        'product_id' => $item['product_id'],
                        'variant_id' => $item['variant_id'] ?? null,
                        'branch_id' => $receipt->branch_id,
                        'location_id' => $receipt->location_id,
                        'transaction_type' => 'purchase',
                        'reference_type' => 'purchase_receipt',
                        'reference_id' => $receipt->id,
                        'reference_number' => $receipt->code,
                        'quantity' => $item['accepted_quantity'],
                        'unit_cost' => $item['unit_cost'],
                        'batch_number' => $item['batch_number'] ?? null,
                        'serial_number' => $item['serial_number'] ?? null,
                        'lot_number' => $item['lot_number'] ?? null,
                        'expiry_date' => $item['expiry_date'] ?? null,
                        'valuation_method' => 'fifo',
                        'notes' => "Purchase receipt: {$receipt->code}",
                    ]);
                }
            }

            // Update PO status if fully received
            if ($purchaseOrder->isFullyReceived()) {
                $this->purchaseOrderRepository->update($purchaseOrder->id, [
                    'status' => 'completed',
                    'actual_delivery_date' => $receipt->receipt_date,
                ]);
            }

            DB::commit();

            return $receipt->load(['purchaseOrder', 'vendor', 'items.product']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to create purchase receipt: ' . $e->getMessage());
        }
    }

    /**
     * Accept purchase receipt
     */
    public function acceptPurchaseReceipt(int $id): mixed
    {
        DB::beginTransaction();

        try {
            $receipt = $this->purchaseReceiptRepository->findById($id);

            if (!$receipt) {
                throw new ServiceException('Purchase receipt not found');
            }

            if ($receipt->status !== 'draft') {
                throw new ServiceException('Purchase receipt is not in draft status');
            }

            $this->purchaseReceiptRepository->update($id, [
                'status' => 'accepted',
                'accepted_by' => Auth::id(),
                'accepted_at' => now(),
            ]);

            DB::commit();

            return $this->purchaseReceiptRepository->findById($id);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to accept purchase receipt: ' . $e->getMessage());
        }
    }

    /**
     * Create purchase return with items and auto stock-out
     */
    public function createPurchaseReturn(array $data): mixed
    {
        DB::beginTransaction();

        try {
            // Validate purchase receipt
            $receipt = $this->purchaseReceiptRepository->findById($data['purchase_receipt_id']);
            
            if (!$receipt) {
                throw new ServiceException('Purchase receipt not found');
            }

            // Generate return code if not provided
            if (empty($data['code'])) {
                $data['code'] = $this->generateReturnCode();
            }

            // Extract items from data
            $items = $data['items'] ?? [];
            unset($data['items']);

            // Set default values
            $data['purchase_order_id'] = $data['purchase_order_id'] ?? $receipt->purchase_order_id;
            $data['vendor_id'] = $data['vendor_id'] ?? $receipt->vendor_id;
            $data['organization_id'] = $data['organization_id'] ?? $receipt->organization_id;
            $data['branch_id'] = $data['branch_id'] ?? $receipt->branch_id;
            $data['location_id'] = $data['location_id'] ?? $receipt->location_id;
            $data['status'] = $data['status'] ?? 'draft';
            $data['approval_status'] = $data['approval_status'] ?? 'pending';
            $data['refund_status'] = $data['refund_status'] ?? 'pending';

            // Create purchase return
            $return = $this->purchaseReceiptRepository->model->make()->purchaseReturn()->create($data);

            // Process items and create stock ledger entries
            foreach ($items as $item) {
                $item['tenant_id'] = $return->tenant_id;
                $item['created_by'] = Auth::id();

                $return->items()->create($item);

                // Auto stock-out returned quantity
                if ($item['quantity'] > 0) {
                    $this->inventoryService->stockOut([
                        'product_id' => $item['product_id'],
                        'variant_id' => $item['variant_id'] ?? null,
                        'branch_id' => $return->branch_id,
                        'location_id' => $return->location_id,
                        'transaction_type' => 'return_outbound',
                        'reference_type' => 'purchase_return',
                        'reference_id' => $return->id,
                        'reference_number' => $return->code,
                        'quantity' => $item['quantity'],
                        'unit_cost' => $item['unit_cost'],
                        'batch_number' => $item['batch_number'] ?? null,
                        'serial_number' => $item['serial_number'] ?? null,
                        'lot_number' => $item['lot_number'] ?? null,
                        'notes' => "Purchase return: {$return->code}",
                    ]);
                }
            }

            DB::commit();

            return $return->load(['purchaseOrder', 'purchaseReceipt', 'vendor', 'items.product']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to create purchase return: ' . $e->getMessage());
        }
    }

    /**
     * Approve purchase return
     */
    public function approvePurchaseReturn(int $id): mixed
    {
        DB::beginTransaction();

        try {
            $return = $this->purchaseReceiptRepository->model->make()->purchaseReturn()->findOrFail($id);

            if ($return->approval_status !== 'pending') {
                throw new ServiceException('Purchase return is not pending approval');
            }

            $return->update([
                'approval_status' => 'approved',
                'status' => 'completed',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            DB::commit();

            return $return;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to approve purchase return: ' . $e->getMessage());
        }
    }

    /**
     * Calculate purchase order totals
     */
    protected function calculatePurchaseOrderTotals(array &$data, array $items): void
    {
        $subtotal = 0;

        foreach ($items as $item) {
            $lineTotal = $item['quantity'] * $item['unit_price'];
            
            // Apply item discount
            if (isset($item['discount_type']) && $item['discount_type'] === 'percentage') {
                $lineTotal -= $lineTotal * ($item['discount_percentage'] / 100);
            } elseif (isset($item['discount_type']) && $item['discount_type'] === 'flat') {
                $lineTotal -= $item['discount_amount'];
            }

            $subtotal += $lineTotal;
        }

        $data['subtotal'] = $subtotal;

        // Apply order-level discount
        if (isset($data['discount_type']) && $data['discount_type'] === 'percentage') {
            $data['discount_amount'] = $subtotal * ($data['discount_percentage'] / 100);
        }

        $totalAfterDiscount = $subtotal - ($data['discount_amount'] ?? 0);

        // Calculate total
        $data['total_amount'] = $totalAfterDiscount 
            + ($data['tax_amount'] ?? 0) 
            + ($data['shipping_amount'] ?? 0) 
            + ($data['other_charges'] ?? 0);

        $data['balance_amount'] = $data['total_amount'] - ($data['paid_amount'] ?? 0);
    }

    /**
     * Calculate line total for item
     */
    protected function calculateLineTotal(array &$item): void
    {
        $lineTotal = $item['quantity'] * $item['unit_price'];
        
        // Apply discount
        if (isset($item['discount_type']) && $item['discount_type'] === 'percentage') {
            $lineTotal -= $lineTotal * ($item['discount_percentage'] / 100);
        } elseif (isset($item['discount_type']) && $item['discount_type'] === 'flat') {
            $lineTotal -= $item['discount_amount'];
        }

        // Add tax
        $lineTotal += $item['tax_amount'] ?? 0;

        $item['line_total'] = $lineTotal;
    }

    /**
     * Generate unique vendor code
     */
    protected function generateVendorCode(): string
    {
        $prefix = 'VND';
        $timestamp = now()->format('Ymd');
        $random = str_pad((string) mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$timestamp}-{$random}";
    }

    /**
     * Generate unique purchase order code
     */
    protected function generatePurchaseOrderCode(): string
    {
        $prefix = 'PO';
        $timestamp = now()->format('Ymd');
        $random = str_pad((string) mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$timestamp}-{$random}";
    }

    /**
     * Generate unique receipt code
     */
    protected function generateReceiptCode(): string
    {
        $prefix = 'GRN';
        $timestamp = now()->format('Ymd');
        $random = str_pad((string) mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$timestamp}-{$random}";
    }

    /**
     * Generate unique return code
     */
    protected function generateReturnCode(): string
    {
        $prefix = 'PRN';
        $timestamp = now()->format('Ymd');
        $random = str_pad((string) mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$timestamp}-{$random}";
    }

    /**
     * Search vendors
     */
    public function searchVendors(string $query, array $filters = []): mixed
    {
        return $this->repository->search($query, $filters);
    }

    /**
     * Get vendor statistics
     */
    public function getVendorStatistics(): array
    {
        return [
            'total' => $this->repository->count(),
            'active' => $this->repository->count(['is_active' => true, 'status' => 'active']),
            'inactive' => $this->repository->count(['status' => 'inactive']),
            'verified' => $this->repository->count(['is_verified' => true]),
            'individual' => $this->repository->count(['type' => 'individual']),
            'business' => $this->repository->count(['type' => 'business']),
        ];
    }
}
