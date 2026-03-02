<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Repositories;

use App\Shared\Abstractions\BaseRepository;
use Modules\Sales\Domain\Contracts\SalesOrderRepositoryInterface;
use Modules\Sales\Domain\Entities\SalesOrder;
use Modules\Sales\Domain\Entities\SalesOrderLine;
use Modules\Sales\Infrastructure\Models\SalesOrderLineModel;
use Modules\Sales\Infrastructure\Models\SalesOrderModel;

class SalesOrderRepository extends BaseRepository implements SalesOrderRepositoryInterface
{
    protected function model(): string
    {
        return SalesOrderModel::class;
    }

    public function findById(int $id, int $tenantId): ?SalesOrder
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
            'items' => $paginator->getCollection()->map(fn (SalesOrderModel $m) => $this->toDomain($m))->all(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
        ];
    }

    public function findByOrderNumber(string $orderNumber, int $tenantId): ?SalesOrder
    {
        $model = $this->newQuery()
            ->with('lines')
            ->where('order_number', $orderNumber)
            ->where('tenant_id', $tenantId)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function save(SalesOrder $order): SalesOrder
    {
        if ($order->id !== null) {
            $model = $this->newQuery()
                ->where('id', $order->id)
                ->where('tenant_id', $order->tenantId)
                ->firstOrFail();
        } else {
            $model = new SalesOrderModel;
            $model->tenant_id = $order->tenantId;
        }

        $model->order_number = $order->orderNumber;
        $model->customer_name = $order->customerName;
        $model->customer_email = $order->customerEmail;
        $model->customer_phone = $order->customerPhone;
        $model->status = $order->status;
        $model->order_date = $order->orderDate;
        $model->due_date = $order->dueDate;
        $model->notes = $order->notes;
        $model->currency = $order->currency;
        $model->subtotal = $order->subtotal;
        $model->tax_amount = $order->taxAmount;
        $model->discount_amount = $order->discountAmount;
        $model->total_amount = $order->totalAmount;
        $model->save();

        if (! empty($order->lines)) {
            $model->lines()->delete();

            foreach ($order->lines as $line) {
                $lineModel = new SalesOrderLineModel;
                $lineModel->sales_order_id = $model->id;
                $lineModel->product_id = $line->productId;
                $lineModel->description = $line->description;
                $lineModel->quantity = $line->quantity;
                $lineModel->unit_price = $line->unitPrice;
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
            throw new \DomainException("Sales order with ID {$id} not found.");
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

        return "SO-{$tenantId}-{$sequence}";
    }

    private function toDomain(SalesOrderModel $model): SalesOrder
    {
        $lines = $model->lines->map(function (SalesOrderLineModel $line): SalesOrderLine {
            return new SalesOrderLine(
                id: $line->id,
                salesOrderId: $line->sales_order_id,
                productId: $line->product_id,
                description: $line->description,
                quantity: bcadd($line->quantity, '0', 4),
                unitPrice: bcadd($line->unit_price, '0', 4),
                taxRate: bcadd($line->tax_rate, '0', 4),
                discountRate: bcadd($line->discount_rate, '0', 4),
                lineTotal: bcadd($line->line_total, '0', 4),
                createdAt: $line->created_at?->toIso8601String(),
                updatedAt: $line->updated_at?->toIso8601String(),
            );
        })->all();

        return new SalesOrder(
            id: $model->id,
            tenantId: $model->tenant_id,
            orderNumber: $model->order_number,
            customerName: $model->customer_name,
            customerEmail: $model->customer_email,
            customerPhone: $model->customer_phone,
            status: $model->status,
            orderDate: $model->order_date instanceof \Carbon\Carbon
                                ? $model->order_date->toDateString()
                                : (string) $model->order_date,
            dueDate: $model->due_date instanceof \Carbon\Carbon
                                ? $model->due_date->toDateString()
                                : $model->due_date,
            notes: $model->notes,
            currency: $model->currency,
            subtotal: bcadd($model->subtotal, '0', 4),
            taxAmount: bcadd($model->tax_amount, '0', 4),
            discountAmount: bcadd($model->discount_amount, '0', 4),
            totalAmount: bcadd($model->total_amount, '0', 4),
            lines: $lines,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }
}
