<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Enums\OrderStatus;
use App\Events\OrderCreated;
use App\Models\Order;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function paginate(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Order::where('tenant_id', $tenantId)->with('lines');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function create(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $lines = $data['lines'] ?? [];
            unset($data['lines']);

            $data['status'] ??= OrderStatus::Draft;
            $data['order_number'] ??= 'ORD-'.strtoupper(Str::random(8));

            [$subtotal, $taxAmount, $total] = $this->calculateTotals($lines, $data['discount_amount'] ?? '0');

            $data['subtotal'] = $subtotal;
            $data['tax_amount'] = $taxAmount;
            $data['total'] = $total;

            $order = Order::create($data);

            foreach ($lines as $line) {
                $line['order_id'] = $order->id;
                $line['line_total'] = $this->calculateLineTotal($line);
                $order->lines()->create($line);
            }

            $this->auditService->log(
                action: AuditAction::Created,
                auditableType: Order::class,
                auditableId: $order->id,
                newValues: ['order_number' => $order->order_number, 'total' => $order->total]
            );

            $freshOrder = $order->fresh(['lines']);
            Event::dispatch(new OrderCreated($freshOrder));

            return $freshOrder;
        });
    }

    public function confirm(string $id): Order
    {
        return DB::transaction(function () use ($id) {
            $order = Order::lockForUpdate()->findOrFail($id);

            if ($order->status !== OrderStatus::Draft && $order->status !== OrderStatus::Pending) {
                throw new \RuntimeException('Only draft or pending orders can be confirmed.');
            }

            $order->update([
                'status' => OrderStatus::Confirmed,
                'confirmed_at' => now(),
            ]);

            $this->auditService->log(
                action: AuditAction::Updated,
                auditableType: Order::class,
                auditableId: $order->id,
                newValues: ['status' => OrderStatus::Confirmed->value]
            );

            return $order->fresh();
        });
    }

    public function cancel(string $id): Order
    {
        return DB::transaction(function () use ($id) {
            $order = Order::lockForUpdate()->findOrFail($id);

            if (in_array($order->status, [OrderStatus::Delivered, OrderStatus::Completed], true)) {
                throw new \RuntimeException('Cannot cancel a completed or delivered order.');
            }

            $order->update([
                'status' => OrderStatus::Cancelled,
                'cancelled_at' => now(),
            ]);

            return $order->fresh();
        });
    }

    private function calculateTotals(array $lines, string $orderDiscount): array
    {
        $subtotal = '0';
        $taxAmount = '0';

        foreach ($lines as $line) {
            $qty = (string) ($line['quantity'] ?? '1');
            $unitPrice = (string) ($line['unit_price'] ?? '0');
            $discAmt = (string) ($line['discount_amount'] ?? '0');
            $taxRate = (string) ($line['tax_rate'] ?? '0');

            $lineBase = bcsub(bcmul($qty, $unitPrice, 8), $discAmt, 8);
            $lineTax = bcdiv(bcmul($lineBase, $taxRate, 8), '100', 8);

            $subtotal = bcadd($subtotal, $lineBase, 8);
            $taxAmount = bcadd($taxAmount, $lineTax, 8);
        }

        $afterDiscount = bcsub($subtotal, (string) $orderDiscount, 8);
        $total = bcadd($afterDiscount, $taxAmount, 8);

        return [$subtotal, $taxAmount, $total];
    }

    private function calculateLineTotal(array $line): string
    {
        $qty = (string) ($line['quantity'] ?? '1');
        $unitPrice = (string) ($line['unit_price'] ?? '0');
        $discAmt = (string) ($line['discount_amount'] ?? '0');
        $taxAmt = (string) ($line['tax_amount'] ?? '0');

        return bcadd(bcsub(bcmul($qty, $unitPrice, 8), $discAmt, 8), $taxAmt, 8);
    }
}
