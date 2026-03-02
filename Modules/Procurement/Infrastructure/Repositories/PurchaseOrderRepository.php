<?php

declare(strict_types=1);

namespace Modules\Procurement\Infrastructure\Repositories;

use App\Shared\Abstractions\BaseRepository;
use Modules\Procurement\Domain\Contracts\PurchaseOrderRepositoryInterface;
use Modules\Procurement\Domain\Entities\PurchaseOrder;
use Modules\Procurement\Domain\Entities\PurchaseOrderLine;
use Modules\Procurement\Infrastructure\Models\PurchaseOrderLineModel;
use Modules\Procurement\Infrastructure\Models\PurchaseOrderModel;

class PurchaseOrderRepository extends BaseRepository implements PurchaseOrderRepositoryInterface
{
    protected function model(): string
    {
        return PurchaseOrderModel::class;
    }

    public function findById(int $id, int $tenantId): ?PurchaseOrder
    {
        $model = $this->newQuery()
            ->with('lines')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findAll(int $tenantId, int $page = 1, int $perPage = 25): array
    {
        $paginator = $this->newQuery()
            ->with('lines')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->getCollection()->map(fn (PurchaseOrderModel $m) => $this->toDomain($m))->all(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
        ];
    }

    public function findByOrderNumber(string $orderNumber, int $tenantId): ?PurchaseOrder
    {
        $model = $this->newQuery()
            ->with('lines')
            ->where('order_number', $orderNumber)
            ->where('tenant_id', $tenantId)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function save(PurchaseOrder $order): PurchaseOrder
    {
        if ($order->id !== null) {
            $model = $this->newQuery()
                ->where('id', $order->id)
                ->where('tenant_id', $order->tenantId)
                ->firstOrFail();
        } else {
            $model = new PurchaseOrderModel;
            $model->tenant_id = $order->tenantId;
        }

        $model->supplier_id = $order->supplierId;
        $model->order_number = $order->orderNumber;
        $model->status = $order->status;
        $model->order_date = $order->orderDate;
        $model->expected_delivery_date = $order->expectedDeliveryDate;
        $model->notes = $order->notes;
        $model->currency = $order->currency;
        $model->subtotal = $order->subtotal;
        $model->tax_amount = $order->taxAmount;
        $model->discount_amount = $order->discountAmount;
        $model->total_amount = $order->totalAmount;
        $model->save();

        if (! empty($order->lines)) {
            foreach ($order->lines as $line) {
                if ($line->id !== null) {
                    $lineModel = PurchaseOrderLineModel::find($line->id);
                    if ($lineModel !== null) {
                        $lineModel->quantity_received = $line->quantityReceived;
                        $lineModel->save();

                        continue;
                    }
                }

                $lineModel = new PurchaseOrderLineModel;
                $lineModel->purchase_order_id = $model->id;
                $lineModel->product_id = $line->productId;
                $lineModel->description = $line->description;
                $lineModel->quantity_ordered = $line->quantityOrdered;
                $lineModel->quantity_received = $line->quantityReceived;
                $lineModel->unit_cost = $line->unitCost;
                $lineModel->tax_rate = $line->taxRate;
                $lineModel->discount_rate = $line->discountRate;
                $lineModel->line_total = $line->lineTotal;
                $lineModel->save();
            }
        }

        $model->load('lines');

        return $this->toDomain($model);
    }

    public function delete(int $id, int $tenantId): void
    {
        $model = $this->newQuery()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($model === null) {
            throw new \DomainException("Purchase order with ID {$id} not found.");
        }

        $model->delete();
    }

    public function nextOrderNumber(int $tenantId): string
    {
        $count = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->withTrashed()
            ->count();

        $sequence = str_pad((string) ($count + 1), 6, '0', STR_PAD_LEFT);

        return "PO-{$tenantId}-{$sequence}";
    }

    private function toDomain(PurchaseOrderModel $model): PurchaseOrder
    {
        $lines = $model->lines->map(function (PurchaseOrderLineModel $line): PurchaseOrderLine {
            return new PurchaseOrderLine(
                id: $line->id,
                purchaseOrderId: $line->purchase_order_id,
                productId: $line->product_id,
                description: $line->description,
                quantityOrdered: bcadd((string) $line->quantity_ordered, '0', 4),
                quantityReceived: bcadd((string) $line->quantity_received, '0', 4),
                unitCost: bcadd((string) $line->unit_cost, '0', 4),
                taxRate: bcadd((string) $line->tax_rate, '0', 4),
                discountRate: bcadd((string) $line->discount_rate, '0', 4),
                lineTotal: bcadd((string) $line->line_total, '0', 4),
                createdAt: $line->created_at?->toIso8601String(),
                updatedAt: $line->updated_at?->toIso8601String(),
            );
        })->all();

        return new PurchaseOrder(
            id: $model->id,
            tenantId: $model->tenant_id,
            supplierId: $model->supplier_id,
            orderNumber: $model->order_number,
            status: $model->status,
            orderDate: $model->order_date instanceof \Carbon\Carbon
                                      ? $model->order_date->toDateString()
                                      : (string) $model->order_date,
            expectedDeliveryDate: $model->expected_delivery_date instanceof \Carbon\Carbon
                                      ? $model->expected_delivery_date->toDateString()
                                      : $model->expected_delivery_date,
            notes: $model->notes,
            currency: $model->currency,
            subtotal: bcadd((string) $model->subtotal, '0', 4),
            taxAmount: bcadd((string) $model->tax_amount, '0', 4),
            discountAmount: bcadd((string) $model->discount_amount, '0', 4),
            totalAmount: bcadd((string) $model->total_amount, '0', 4),
            lines: $lines,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }
}
