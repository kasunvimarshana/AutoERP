<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\Purchase;
use App\Models\PurchaseLine;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly InventoryService $inventoryService
    ) {}

    public function paginate(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Purchase::where('tenant_id', $tenantId)
            ->with(['supplier', 'businessLocation', 'createdBy']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }
        if (isset($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        return $query->orderByDesc('purchase_date')->paginate($perPage);
    }

    public function create(array $data, string $tenantId, string $userId): Purchase
    {
        return DB::transaction(function () use ($data, $tenantId, $userId) {
            // Calculate totals using BCMath
            $subtotal = '0';
            $taxAmount = '0';
            $discountAmount = $data['discount_amount'] ?? '0';
            $shippingAmount = $data['shipping_amount'] ?? '0';

            $lines = $data['lines'] ?? [];
            foreach ($lines as $line) {
                $lineTotal = bcmul($line['unit_cost'], $line['quantity_ordered'], 8);
                $lineDiscount = bcmul($lineTotal, bcdiv($line['discount_percent'] ?? '0', '100', 8), 8);
                $lineTaxBase = bcsub($lineTotal, $lineDiscount, 8);
                $lineTax = bcmul($lineTaxBase, bcdiv($line['tax_percent'] ?? '0', '100', 8), 8);
                $subtotal = bcadd($subtotal, $lineTotal, 8);
                $taxAmount = bcadd($taxAmount, $lineTax, 8);
            }

            $total = bcadd(bcadd(bcsub($subtotal, $discountAmount, 8), $taxAmount, 8), $shippingAmount, 8);

            $purchase = Purchase::create([
                'tenant_id' => $tenantId,
                'business_location_id' => $data['business_location_id'] ?? null,
                'reference_no' => $data['reference_no'] ?? 'PO-'.strtoupper(Str::random(8)),
                'status' => $data['status'] ?? 'ordered',
                'supplier_id' => $data['supplier_id'] ?? null,
                'purchase_date' => $data['purchase_date'] ?? now()->toDateString(),
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'shipping_amount' => $shippingAmount,
                'total' => $total,
                'paid_amount' => $data['paid_amount'] ?? '0',
                'payment_status' => $data['payment_status'] ?? 'pending',
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($lines as $line) {
                $lineTotal = bcmul($line['unit_cost'], $line['quantity_ordered'], 8);
                $lineDiscount = bcmul($lineTotal, bcdiv($line['discount_percent'] ?? '0', '100', 8), 8);
                $lineTaxBase = bcsub($lineTotal, $lineDiscount, 8);
                $lineTax = bcmul($lineTaxBase, bcdiv($line['tax_percent'] ?? '0', '100', 8), 8);
                $finalLineTotal = bcadd(bcsub($lineTotal, $lineDiscount, 8), $lineTax, 8);

                PurchaseLine::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $line['product_id'],
                    'product_variant_id' => $line['product_variant_id'] ?? null,
                    'quantity_ordered' => $line['quantity_ordered'],
                    'quantity_received' => '0',
                    'unit_cost' => $line['unit_cost'],
                    'discount_percent' => $line['discount_percent'] ?? '0',
                    'discount_amount' => $lineDiscount,
                    'tax_percent' => $line['tax_percent'] ?? '0',
                    'tax_amount' => $lineTax,
                    'line_total' => $finalLineTotal,
                ]);
            }

            $this->auditService->log(
                action: AuditAction::Created,
                auditableType: Purchase::class,
                auditableId: $purchase->id,
                newValues: ['reference_no' => $purchase->reference_no, 'total' => $total]
            );

            return $purchase->fresh(['lines', 'supplier', 'businessLocation']);
        });
    }

    public function receive(string $id, array $receivedLines, string $tenantId): Purchase
    {
        return DB::transaction(function () use ($id, $receivedLines, $tenantId) {
            $purchase = Purchase::where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->findOrFail($id);

            if (in_array($purchase->status, ['received', 'cancelled'])) {
                throw new \RuntimeException('Cannot receive a purchase that is already received or cancelled.');
            }

            $allReceived = true;
            $anyReceived = false;

            foreach ($receivedLines as $line) {
                $purchaseLine = PurchaseLine::where('purchase_id', $id)
                    ->lockForUpdate()
                    ->findOrFail($line['purchase_line_id']);

                $newReceived = bcadd($purchaseLine->quantity_received, $line['quantity_received'], 8);
                $purchaseLine->update(['quantity_received' => $newReceived]);

                // Update inventory
                $this->inventoryService->adjust(
                    tenantId: $tenantId,
                    warehouseId: $line['warehouse_id'] ?? $purchase->business_location_id,
                    productId: $purchaseLine->product_id,
                    quantity: $line['quantity_received'],
                    movementType: 'purchase_receipt',
                    variantId: $purchaseLine->product_variant_id,
                    notes: 'Purchase receipt: '.$purchase->reference_no,
                    referenceType: Purchase::class,
                    referenceId: $purchase->id
                );

                if (bccomp($purchaseLine->quantity_received, $purchaseLine->quantity_ordered, 8) < 0) {
                    $allReceived = false;
                }
                $anyReceived = true;
            }

            if ($anyReceived) {
                $purchase->update(['status' => $allReceived ? 'received' : 'partial']);
            }

            $this->auditService->log(
                action: AuditAction::Updated,
                auditableType: Purchase::class,
                auditableId: $purchase->id,
                newValues: ['status' => $purchase->status]
            );

            return $purchase->fresh(['lines', 'supplier']);
        });
    }

    public function cancel(string $id, string $tenantId): Purchase
    {
        return DB::transaction(function () use ($id, $tenantId) {
            $purchase = Purchase::where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->findOrFail($id);

            if ($purchase->status === 'received') {
                throw new \RuntimeException('Cannot cancel a fully received purchase.');
            }

            $purchase->update(['status' => 'cancelled']);

            $this->auditService->log(
                action: AuditAction::Updated,
                auditableType: Purchase::class,
                auditableId: $purchase->id,
                newValues: ['status' => 'cancelled']
            );

            return $purchase->fresh();
        });
    }
}
