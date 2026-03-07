<?php

namespace App\Modules\Order\Repositories;

use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class OrderRepository implements OrderRepositoryInterface
{
    public function all(array $filters = []): Collection
    {
        $query = Order::with('items.product');

        foreach ($filters as $column => $value) {
            $query->where($column, $value);
        }

        return $query->get();
    }

    public function find(int $id): ?Order
    {
        return Order::with('items.product')->find($id);
    }

    public function findByOrderNumber(string $orderNumber): ?Order
    {
        return Order::with('items.product')->where('order_number', $orderNumber)->first();
    }

    public function create(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $subtotal = collect($items)->sum(fn($item) => $item['quantity'] * $item['unit_price']);
            $tax      = $data['tax'] ?? 0;
            $data     = array_merge($data, [
                'subtotal' => $subtotal,
                'total'    => $subtotal + $tax,
                'status'   => $data['status'] ?? 'pending',
            ]);

            $order = Order::create($data);

            foreach ($items as $item) {
                OrderItem::create([
                    'order_id'    => $order->id,
                    'product_id'  => $item['product_id'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            return $order->load('items.product');
        });
    }

    public function update(int $id, array $data): Order
    {
        $order = Order::findOrFail($id);
        unset($data['items']);
        $order->update($data);
        return $order->fresh('items.product');
    }

    public function delete(int $id): bool
    {
        $order = Order::findOrFail($id);
        return $order->delete();
    }

    public function paginate(
        int $perPage,
        array $filters,
        ?string $search,
        string $sortBy,
        string $sortDir
    ): LengthAwarePaginator {
        $query = Order::with('items.product');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%");
            });
        }

        $allowedSorts = ['order_number', 'customer_name', 'status', 'total', 'created_at'];
        $sortBy  = in_array($sortBy, $allowedSorts) ? $sortBy : 'created_at';
        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortDir)->paginate($perPage);
    }

    public function updateStatus(int $id, string $status): Order
    {
        $order = Order::findOrFail($id);
        $order->update(['status' => $status]);
        return $order->fresh('items.product');
    }

    public function updateSagaState(int $id, array $sagaState): Order
    {
        $order = Order::findOrFail($id);
        $order->update(['saga_state' => $sagaState]);
        return $order->fresh();
    }
}
