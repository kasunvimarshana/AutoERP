<?php

declare(strict_types=1);

namespace Modules\Procurement\Application\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Core\Application\Helpers\DecimalHelper;
use Modules\Core\Domain\Contracts\ServiceContract;
use Modules\Inventory\Application\DTOs\StockTransactionDTO;
use Modules\Inventory\Domain\Contracts\InventoryServiceContract;
use Modules\Procurement\Application\DTOs\CreatePurchaseOrderDTO;
use Modules\Procurement\Application\DTOs\CreateVendorBillDTO;
use Modules\Procurement\Application\DTOs\CreateVendorDTO;
use Modules\Procurement\Domain\Contracts\ProcurementRepositoryContract;
use Modules\Procurement\Domain\Contracts\VendorBillRepositoryContract;
use Modules\Procurement\Domain\Contracts\VendorRepositoryContract;
use Modules\Procurement\Domain\Entities\GoodsReceipt;
use Modules\Procurement\Domain\Entities\PurchaseOrder;
use Modules\Procurement\Domain\Entities\PurchaseOrderLine;
use Modules\Procurement\Domain\Entities\Vendor;
use Modules\Procurement\Domain\Entities\VendorBill;

/**
 * Procurement service.
 *
 * Orchestrates all procurement use cases.
 * All arithmetic uses DecimalHelper (BCMath) — no float allowed.
 */
class ProcurementService implements ServiceContract
{
    public function __construct(
        private readonly ProcurementRepositoryContract $procurementRepository,
        private readonly VendorRepositoryContract $vendorRepository,
        private readonly VendorBillRepositoryContract $vendorBillRepository,
        private readonly ?InventoryServiceContract $inventoryService = null,
    ) {}

    /**
     * Create a new purchase order with order lines.
     *
     * Calculates line totals, subtotal, and grand total using BCMath.
     * Wrapped in a DB transaction for atomicity.
     */
    public function createPurchaseOrder(CreatePurchaseOrderDTO $dto): PurchaseOrder
    {
        return DB::transaction(function () use ($dto): PurchaseOrder {
            $subtotal  = '0';
            $taxAmount = '0.0000';
            $lineData  = [];

            foreach ($dto->lines as $line) {
                $quantity  = (string) $line['quantity'];
                $unitCost  = (string) $line['unit_cost'];

                // line_total = quantity × unit_cost  [intermediate: 8dp, stored at 4dp]
                $lineTotal = DecimalHelper::mul($quantity, $unitCost, DecimalHelper::SCALE_INTERMEDIATE);
                $lineTotal = DecimalHelper::round($lineTotal, DecimalHelper::SCALE_STANDARD);

                $subtotal = DecimalHelper::add($subtotal, $lineTotal, DecimalHelper::SCALE_STANDARD);

                $lineData[] = [
                    'product_id' => (int) $line['product_id'],
                    'uom_id'     => (int) $line['uom_id'],
                    'quantity'   => DecimalHelper::round($quantity, DecimalHelper::SCALE_STANDARD),
                    'unit_cost'  => DecimalHelper::round($unitCost, DecimalHelper::SCALE_STANDARD),
                    'line_total' => $lineTotal,
                ];
            }

            // total_amount = subtotal + tax_amount (tax_amount defaults to 0 at creation)
            $totalAmount = DecimalHelper::add($subtotal, $taxAmount, DecimalHelper::SCALE_STANDARD);

            /** @var PurchaseOrder $order */
            $order = $this->procurementRepository->create([
                'vendor_id'              => $dto->vendorId,
                'order_number'           => $this->generateOrderNumber(),
                'status'                 => 'draft',
                'order_date'             => $dto->orderDate,
                'expected_delivery_date' => $dto->expectedDeliveryDate,
                'currency_code'          => $dto->currencyCode,
                'subtotal'               => $subtotal,
                'tax_amount'             => $taxAmount,
                'total_amount'           => $totalAmount,
                'notes'                  => $dto->notes,
            ]);

            foreach ($lineData as $line) {
                $order->lines()->create(array_merge($line, ['tenant_id' => $order->tenant_id]));
            }

            return $order->load('lines');
        });
    }

    /**
     * Receive goods against a purchase order.
     *
     * When an `InventoryServiceContract` is available and a line entry includes
     * a `warehouse_id`, a `purchase_receipt` stock transaction is automatically
     * recorded for that line so inventory is updated in real time.
     *
     * @param array<int, array{purchase_order_line_id: int, quantity_received: string, unit_cost: string, warehouse_id?: int}> $linesReceived
     */
    public function receiveGoods(int $purchaseOrderId, array $linesReceived): GoodsReceipt
    {
        return DB::transaction(function () use ($purchaseOrderId, $linesReceived): GoodsReceipt {
            /** @var PurchaseOrder $order */
            $order = $this->procurementRepository->findOrFail($purchaseOrderId);

            /** @var GoodsReceipt $receipt */
            $receipt = $order->goodsReceipts()->create([
                'tenant_id'      => $order->tenant_id,
                'receipt_number' => $this->generateReceiptNumber(),
                'status'         => 'received',
                'received_at'    => now(),
            ]);

            foreach ($linesReceived as $line) {
                $qtyReceived = DecimalHelper::round((string) $line['quantity_received'], DecimalHelper::SCALE_STANDARD);
                $unitCost    = DecimalHelper::round((string) $line['unit_cost'], DecimalHelper::SCALE_STANDARD);

                $receipt->lines()->create([
                    'tenant_id'              => $order->tenant_id,
                    'purchase_order_line_id' => (int) $line['purchase_order_line_id'],
                    'quantity_received'      => $qtyReceived,
                    'unit_cost'              => $unitCost,
                ]);

                // Automatic inventory update: record a purchase_receipt transaction
                // when the caller provides a warehouse_id for the received line.
                if ($this->inventoryService !== null && isset($line['warehouse_id'])) {
                    // PurchaseOrderLine uses HasTenant — the find() is tenant-scoped
                    // via the Eloquent global scope. This is a same-module entity lookup.
                    /** @var PurchaseOrderLine|null $poLine */
                    $poLine = PurchaseOrderLine::where('id', (int) $line['purchase_order_line_id'])
                        ->where('tenant_id', $order->tenant_id)
                        ->first();

                    if ($poLine === null) {
                        throw new \InvalidArgumentException(
                            "PurchaseOrderLine ID {$line['purchase_order_line_id']} not found for tenant."
                        );
                    }

                    $dto = StockTransactionDTO::fromArray([
                        'transaction_type' => 'purchase_receipt',
                        'warehouse_id'     => (int) $line['warehouse_id'],
                        'product_id'       => $poLine->product_id,
                        'uom_id'           => $poLine->uom_id,
                        'quantity'         => $qtyReceived,
                        'unit_cost'        => $unitCost,
                        'notes'            => "Auto-receipt for PO {$order->order_number}",
                    ]);

                    $this->inventoryService->recordTransaction($dto);
                }
            }

            $order->update(['status' => 'goods_received']);

            return $receipt->load('lines');
        });
    }

    /**
     * Perform a three-way match for a purchase order.
     *
     * Compares PO lines vs GoodsReceipt lines vs VendorBill total.
     *
     * @return array{matched: bool, discrepancies: array<string, mixed>}
     */
    public function threeWayMatch(int $purchaseOrderId): array
    {
        /** @var PurchaseOrder $order */
        $order = $this->procurementRepository->findOrFail($purchaseOrderId);
        $order->load(['lines', 'goodsReceipts.lines', 'vendorBills']);

        $discrepancies = [];

        // Build ordered quantities map from PO lines
        $orderedQty = [];
        foreach ($order->lines as $poLine) {
            $orderedQty[$poLine->id] = $poLine->quantity;
        }

        // Sum received quantities per PO line across all receipts
        $receivedQty = [];
        foreach ($order->goodsReceipts as $receipt) {
            foreach ($receipt->lines as $receiptLine) {
                $polId = $receiptLine->purchase_order_line_id;
                $receivedQty[$polId] = DecimalHelper::add(
                    $receivedQty[$polId] ?? '0',
                    $receiptLine->quantity_received,
                    DecimalHelper::SCALE_STANDARD
                );
            }
        }

        // Compare ordered vs received
        foreach ($orderedQty as $poLineId => $ordered) {
            $received = $receivedQty[$poLineId] ?? '0.0000';
            if (! DecimalHelper::equals($ordered, $received)) {
                $discrepancies[] = [
                    'purchase_order_line_id' => $poLineId,
                    'ordered'                => $ordered,
                    'received'               => $received,
                ];
            }
        }

        // Compare PO total_amount vs sum of VendorBill total_amounts
        $billedTotal = '0.0000';
        foreach ($order->vendorBills as $bill) {
            $billedTotal = DecimalHelper::add($billedTotal, $bill->total_amount, DecimalHelper::SCALE_STANDARD);
        }

        if (! DecimalHelper::equals($order->total_amount, $billedTotal)) {
            $discrepancies[] = [
                'field'    => 'total_amount',
                'expected' => $order->total_amount,
                'billed'   => $billedTotal,
            ];
        }

        return [
            'matched'       => empty($discrepancies),
            'discrepancies' => $discrepancies,
        ];
    }

    /**
     * List purchase orders with optional filters.
     *
     * @param array<string, mixed> $filters
     */
    public function listOrders(array $filters = []): Collection
    {
        if (isset($filters['vendor_id'])) {
            return $this->procurementRepository->findByVendor((int) $filters['vendor_id']);
        }

        return $this->procurementRepository->all();
    }

    /**
     * List all vendors with optional active filter.
     *
     * @param array<string, mixed> $filters
     */
    public function listVendors(array $filters = []): Collection
    {
        if (isset($filters['active_only']) && $filters['active_only']) {
            return $this->vendorRepository->findActive();
        }

        return $this->vendorRepository->all();
    }

    /**
     * Create a new vendor.
     */
    public function createVendor(CreateVendorDTO $dto): Vendor
    {
        return DB::transaction(function () use ($dto): Vendor {
            /** @var Vendor $vendor */
            $vendor = $this->vendorRepository->create([
                'name'        => $dto->name,
                'email'       => $dto->email,
                'phone'       => $dto->phone,
                'address'     => $dto->address,
                'vendor_code' => $dto->vendorCode,
                'is_active'   => $dto->isActive,
            ]);

            return $vendor;
        });
    }

    /**
     * Find a single vendor by ID.
     */
    public function showVendor(int $id): Model
    {
        return $this->vendorRepository->findOrFail($id);
    }

    /**
     * Create a vendor bill linked to a purchase order.
     *
     * total_amount is validated as a BCMath-safe string.
     */
    public function createVendorBill(CreateVendorBillDTO $dto): VendorBill
    {
        return DB::transaction(function () use ($dto): VendorBill {
            $totalAmount = DecimalHelper::round($dto->totalAmount, DecimalHelper::SCALE_STANDARD);

            /** @var VendorBill $bill */
            $bill = $this->vendorBillRepository->create([
                'vendor_id'         => $dto->vendorId,
                'purchase_order_id' => $dto->purchaseOrderId,
                'bill_number'       => $this->generateBillNumber(),
                'status'            => 'draft',
                'bill_date'         => $dto->billDate,
                'due_date'          => $dto->dueDate,
                'total_amount'      => $totalAmount,
                'paid_amount'       => '0.0000',
            ]);

            return $bill;
        });
    }

    /**
     * List vendor bills with optional filters.
     *
     * @param array<string, mixed> $filters
     */
    public function listVendorBills(array $filters = []): Collection
    {
        if (isset($filters['vendor_id'])) {
            return $this->vendorBillRepository->findByVendor((int) $filters['vendor_id']);
        }

        if (isset($filters['purchase_order_id'])) {
            return $this->vendorBillRepository->findByPurchaseOrder((int) $filters['purchase_order_id']);
        }

        return $this->vendorBillRepository->all();
    }

    /**
     * Show a single purchase order by ID.
     */
    public function showPurchaseOrder(int|string $id): Model
    {
        return $this->procurementRepository->findOrFail($id);
    }

    /**
     * Update an existing purchase order.
     * @param array<string, mixed> $data
     */
    public function updatePurchaseOrder(int|string $id, array $data): Model
    {
        return DB::transaction(fn () => $this->procurementRepository->update($id, $data));
    }

    /**
     * Show a single vendor bill by ID.
     */
    public function showVendorBill(int|string $id): Model
    {
        return $this->vendorBillRepository->findOrFail($id);
    }

    /**
     * Update an existing vendor.
     * @param array<string, mixed> $data
     */
    public function updateVendor(int|string $id, array $data): Model
    {
        return DB::transaction(fn () => $this->vendorRepository->update($id, $data));
    }

    /**
     * Generate a unique purchase order number: PO-{YYYYMMDD}-{token}.
     */
    private function generateOrderNumber(): string
    {
        return 'PO-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid('', true), -6));
    }

    /**
     * Generate a unique goods receipt number: GR-{YYYYMMDD}-{token}.
     */
    private function generateReceiptNumber(): string
    {
        return 'GR-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid('', true), -6));
    }

    /**
     * Generate a unique vendor bill number: VB-{YYYYMMDD}-{token}.
     */
    private function generateBillNumber(): string
    {
        return 'VB-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid('', true), -6));
    }
}
