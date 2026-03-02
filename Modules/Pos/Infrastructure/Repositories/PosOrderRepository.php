<?php

declare(strict_types=1);

namespace Modules\Pos\Infrastructure\Repositories;

use App\Shared\Abstractions\BaseRepository;
use Modules\Pos\Domain\Contracts\PosOrderRepositoryInterface;
use Modules\Pos\Domain\Entities\PosOrder;
use Modules\Pos\Domain\Entities\PosOrderLine;
use Modules\Pos\Domain\Entities\PosPayment;
use Modules\Pos\Infrastructure\Models\PosOrderLineModel;
use Modules\Pos\Infrastructure\Models\PosOrderModel;
use Modules\Pos\Infrastructure\Models\PosPaymentModel;

class PosOrderRepository extends BaseRepository implements PosOrderRepositoryInterface
{
    protected function model(): string
    {
        return PosOrderModel::class;
    }

    public function save(PosOrder $order): PosOrder
    {
        if ($order->id !== null) {
            /** @var PosOrderModel $model */
            $model = $this->newQuery()
                ->where('tenant_id', $order->tenantId)
                ->findOrFail($order->id);
        } else {
            $model = new PosOrderModel;
            $model->tenant_id = $order->tenantId;
        }

        $model->pos_session_id = $order->posSessionId;
        $model->reference = $order->reference;
        $model->status = $order->status;
        $model->currency = $order->currency;
        $model->subtotal = $order->subtotal;
        $model->tax_amount = $order->taxAmount;
        $model->discount_amount = $order->discountAmount;
        $model->total_amount = $order->totalAmount;
        $model->paid_amount = $order->paidAmount;
        $model->change_amount = $order->changeAmount;
        $model->notes = $order->notes;
        $model->save();

        return $this->toEntity($model);
    }

    public function findById(int $id, int $tenantId): ?PosOrder
    {
        $model = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function findAll(int $tenantId, int $page, int $perPage): array
    {
        $paginator = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->getCollection()
                ->map(fn (PosOrderModel $m) => $this->toEntity($m))
                ->all(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
        ];
    }

    public function findBySession(int $sessionId, int $tenantId): array
    {
        return $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('pos_session_id', $sessionId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (PosOrderModel $m) => $this->toEntity($m))
            ->all();
    }

    public function saveLines(int $orderId, array $lines): array
    {
        $saved = [];
        foreach ($lines as $line) {
            /** @var PosOrderLine $line */
            $model = new PosOrderLineModel;
            $model->tenant_id = $line->tenantId;
            $model->pos_order_id = $orderId;
            $model->product_id = $line->productId;
            $model->product_name = $line->productName;
            $model->sku = $line->sku;
            $model->quantity = $line->quantity;
            $model->unit_price = $line->unitPrice;
            $model->discount_amount = $line->discountAmount;
            $model->tax_amount = $line->taxAmount;
            $model->line_total = $line->lineTotal;
            $model->save();
            $saved[] = $this->toLineEntity($model);
        }

        return $saved;
    }

    public function findLines(int $orderId, int $tenantId): array
    {
        return PosOrderLineModel::query()
            ->where('tenant_id', $tenantId)
            ->where('pos_order_id', $orderId)
            ->get()
            ->map(fn (PosOrderLineModel $m) => $this->toLineEntity($m))
            ->all();
    }

    public function savePayment(PosPayment $payment): PosPayment
    {
        $model = new PosPaymentModel;
        $model->tenant_id = $payment->tenantId;
        $model->pos_order_id = $payment->posOrderId;
        $model->method = $payment->method;
        $model->amount = $payment->amount;
        $model->currency = $payment->currency;
        $model->reference = $payment->reference;
        $model->save();

        return $this->toPaymentEntity($model);
    }

    public function findPayments(int $orderId, int $tenantId): array
    {
        return PosPaymentModel::query()
            ->where('tenant_id', $tenantId)
            ->where('pos_order_id', $orderId)
            ->get()
            ->map(fn (PosPaymentModel $m) => $this->toPaymentEntity($m))
            ->all();
    }

    public function delete(int $id, int $tenantId): void
    {
        $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->findOrFail($id)
            ->delete();
    }

    private function toEntity(PosOrderModel $model): PosOrder
    {
        return new PosOrder(
            id: $model->id,
            tenantId: $model->tenant_id,
            posSessionId: $model->pos_session_id,
            reference: $model->reference,
            status: $model->status,
            currency: $model->currency,
            subtotal: (string) $model->subtotal,
            taxAmount: (string) $model->tax_amount,
            discountAmount: (string) $model->discount_amount,
            totalAmount: (string) $model->total_amount,
            paidAmount: (string) $model->paid_amount,
            changeAmount: (string) $model->change_amount,
            notes: $model->notes,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }

    private function toLineEntity(PosOrderLineModel $model): PosOrderLine
    {
        return new PosOrderLine(
            id: $model->id,
            tenantId: $model->tenant_id,
            posOrderId: $model->pos_order_id,
            productId: $model->product_id,
            productName: $model->product_name,
            sku: $model->sku,
            quantity: (string) $model->quantity,
            unitPrice: (string) $model->unit_price,
            discountAmount: (string) $model->discount_amount,
            taxAmount: (string) $model->tax_amount,
            lineTotal: (string) $model->line_total,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }

    private function toPaymentEntity(PosPaymentModel $model): PosPayment
    {
        return new PosPayment(
            id: $model->id,
            tenantId: $model->tenant_id,
            posOrderId: $model->pos_order_id,
            method: $model->method,
            amount: (string) $model->amount,
            currency: $model->currency,
            reference: $model->reference,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }
}
