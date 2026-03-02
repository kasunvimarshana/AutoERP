<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Infrastructure\Repositories;

use App\Shared\Abstractions\BaseRepository;
use Modules\Ecommerce\Domain\Contracts\StorefrontOrderRepositoryInterface;
use Modules\Ecommerce\Domain\Entities\StorefrontOrder;
use Modules\Ecommerce\Domain\Entities\StorefrontOrderLine;
use Modules\Ecommerce\Infrastructure\Models\StorefrontOrderLineModel;
use Modules\Ecommerce\Infrastructure\Models\StorefrontOrderModel;

class StorefrontOrderRepository extends BaseRepository implements StorefrontOrderRepositoryInterface
{
    protected function model(): string
    {
        return StorefrontOrderModel::class;
    }

    public function save(StorefrontOrder $order): StorefrontOrder
    {
        if ($order->id !== null) {
            $model = $this->newQuery()
                ->where('tenant_id', $order->tenantId)
                ->findOrFail($order->id);
        } else {
            $model = new StorefrontOrderModel;
            $model->tenant_id = $order->tenantId;
        }

        $model->user_id = $order->userId;
        $model->reference = $order->reference;
        $model->status = $order->status;
        $model->currency = $order->currency;
        $model->subtotal = $order->subtotal;
        $model->tax_amount = $order->taxAmount;
        $model->shipping_amount = $order->shippingAmount;
        $model->discount_amount = $order->discountAmount;
        $model->total_amount = $order->totalAmount;
        $model->billing_name = $order->billingName;
        $model->billing_email = $order->billingEmail;
        $model->billing_phone = $order->billingPhone;
        $model->shipping_address = $order->shippingAddress;
        $model->notes = $order->notes;
        $model->cart_token = $order->cartToken;
        $model->save();

        return $this->toEntity($model);
    }

    public function findById(int $id, int $tenantId): ?StorefrontOrder
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
                ->map(fn (StorefrontOrderModel $m) => $this->toEntity($m))
                ->all(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
        ];
    }

    public function saveLines(int $orderId, array $lines): array
    {
        $saved = [];
        foreach ($lines as $line) {
            $model = new StorefrontOrderLineModel;
            $model->tenant_id = $line->tenantId;
            $model->order_id = $orderId;
            $model->product_id = $line->productId;
            $model->product_name = $line->productName;
            $model->sku = $line->sku;
            $model->quantity = $line->quantity;
            $model->unit_price = $line->unitPrice;
            $model->line_total = $line->lineTotal;
            $model->save();

            $saved[] = $this->toLineEntity($model);
        }

        return $saved;
    }

    public function findLines(int $orderId, int $tenantId): array
    {
        return StorefrontOrderLineModel::where('order_id', $orderId)
            ->where('tenant_id', $tenantId)
            ->get()
            ->map(fn (StorefrontOrderLineModel $m) => $this->toLineEntity($m))
            ->all();
    }

    public function delete(int $id, int $tenantId): void
    {
        $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->findOrFail($id)
            ->delete();
    }

    private function toEntity(StorefrontOrderModel $model): StorefrontOrder
    {
        return new StorefrontOrder(
            id: $model->id,
            tenantId: $model->tenant_id,
            userId: $model->user_id,
            reference: $model->reference,
            status: $model->status,
            currency: $model->currency,
            subtotal: bcadd((string) $model->subtotal, '0', 4),
            taxAmount: bcadd((string) $model->tax_amount, '0', 4),
            shippingAmount: bcadd((string) $model->shipping_amount, '0', 4),
            discountAmount: bcadd((string) $model->discount_amount, '0', 4),
            totalAmount: bcadd((string) $model->total_amount, '0', 4),
            billingName: $model->billing_name,
            billingEmail: $model->billing_email,
            billingPhone: $model->billing_phone,
            shippingAddress: $model->shipping_address,
            notes: $model->notes,
            cartToken: $model->cart_token,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }

    private function toLineEntity(StorefrontOrderLineModel $model): StorefrontOrderLine
    {
        return new StorefrontOrderLine(
            id: $model->id,
            tenantId: $model->tenant_id,
            orderId: $model->order_id,
            productId: $model->product_id,
            productName: $model->product_name,
            sku: $model->sku,
            quantity: bcadd((string) $model->quantity, '0', 4),
            unitPrice: bcadd((string) $model->unit_price, '0', 4),
            lineTotal: bcadd((string) $model->line_total, '0', 4),
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }
}
