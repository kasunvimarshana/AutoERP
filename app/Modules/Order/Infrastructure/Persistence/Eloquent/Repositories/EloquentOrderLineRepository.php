<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Order\Domain\Entities\OrderLine;
use Modules\Order\Domain\RepositoryInterfaces\OrderLineRepositoryInterface;
use Modules\Order\Infrastructure\Persistence\Eloquent\Models\OrderLineModel;

class EloquentOrderLineRepository implements OrderLineRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?OrderLine
    {
        $model = OrderLineModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findByOrder(string $tenantId, string $orderType, string $orderId): array
    {
        return OrderLineModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('order_type', $orderType)
            ->where('order_id', $orderId)
            ->get()
            ->map(fn(OrderLineModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function save(OrderLine $orderLine): void
    {
        /** @var OrderLineModel $model */
        $model = OrderLineModel::withoutGlobalScopes()->findOrNew($orderLine->id);
        $model->fill([
            'tenant_id'   => $orderLine->tenantId,
            'order_type'  => $orderLine->orderType,
            'order_id'    => $orderLine->orderId,
            'product_id'  => $orderLine->productId,
            'variant_id'  => $orderLine->variantId,
            'description' => $orderLine->description,
            'quantity'    => $orderLine->quantity,
            'unit_price'  => $orderLine->unitPrice,
            'discount'    => $orderLine->discount,
            'tax_rate'    => $orderLine->taxRate,
            'line_total'  => $orderLine->lineTotal,
        ]);
        if (!$model->exists) {
            $model->id = $orderLine->id;
        }
        $model->save();
    }

    public function delete(string $tenantId, string $id): void
    {
        OrderLineModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id)?->delete();
    }

    private function mapToEntity(OrderLineModel $model): OrderLine
    {
        return new OrderLine(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            orderType: (string) $model->order_type,
            orderId: (string) $model->order_id,
            productId: (string) $model->product_id,
            variantId: $model->variant_id !== null ? (string) $model->variant_id : null,
            description: $model->description !== null ? (string) $model->description : null,
            quantity: (float) $model->quantity,
            unitPrice: (float) $model->unit_price,
            discount: (float) $model->discount,
            taxRate: (float) $model->tax_rate,
            lineTotal: (float) $model->line_total,
            createdAt: $model->created_at ?? now(),
            updatedAt: $model->updated_at ?? now(),
        );
    }
}
