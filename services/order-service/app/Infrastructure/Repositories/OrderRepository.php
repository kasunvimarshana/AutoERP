<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Domain\Order\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Order Repository
 */
class OrderRepository implements OrderRepositoryInterface
{
    public function findById(string $id): ?Order
    {
        return Order::with('items')->find($id);
    }

    public function findBySagaId(string $sagaId): ?Order
    {
        return Order::where('saga_id', $sagaId)->with('items')->first();
    }

    public function create(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $order = Order::create($data);

            foreach ($items as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'warehouse_id' => $item['warehouse_id'],
                    'product_name' => $item['product_name'] ?? 'Unknown',
                    'product_sku' => $item['product_sku'] ?? '',
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'] ?? 0,
                    'total_price' => ($item['unit_price'] ?? 0) * $item['quantity'],
                ]);
            }

            return $order->load('items');
        });
    }

    public function updateStatus(string $id, string $status, ?array $extra = null): Order
    {
        $order = Order::findOrFail($id);
        $update = array_merge(['status' => $status], $extra ?? []);
        $order->update($update);
        return $order->fresh('items');
    }

    public function getByTenant(string $tenantId, array $params = []): mixed
    {
        $query = Order::where('tenant_id', $tenantId)->with('items')->orderBy('created_at', 'desc');
        if (isset($params['per_page'])) {
            return $query->paginate((int) $params['per_page'], ['*'], 'page', (int) ($params['page'] ?? 1));
        }
        return $query->get();
    }

    public function cancel(string $id, string $reason): Order
    {
        return $this->updateStatus($id, Order::STATUS_CANCELLED, [
            'cancellation_reason' => $reason,
            'cancelled_at' => now(),
        ]);
    }
}
