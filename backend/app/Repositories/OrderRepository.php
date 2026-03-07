<?php

namespace App\Repositories;

use App\Models\Order;

class OrderRepository extends BaseRepository
{
    protected array $searchableColumns = ['order_number', 'notes'];

    protected function model(): string
    {
        return Order::class;
    }

    public function findByOrderNumber(string $orderNumber): ?Order
    {
        return $this->newQuery()->where('order_number', $orderNumber)->first();
    }

    public function getByUser(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->newQuery()->where('user_id', $userId)->with('items.product')->get();
    }

    public function getByStatus(string $status): \Illuminate\Database\Eloquent\Collection
    {
        return $this->newQuery()->where('status', $status)->get();
    }

    public function generateOrderNumber(): string
    {
        $tenantId = app()->bound('current_tenant') ? app('current_tenant')->id : 0;
        $prefix   = 'ORD-' . str_pad($tenantId, 3, '0', STR_PAD_LEFT) . '-';
        $last     = $this->newQuery()->where('order_number', 'LIKE', $prefix . '%')
                                     ->orderByDesc('id')
                                     ->value('order_number');

        $lastNum = $last ? (int) substr($last, strlen($prefix)) : 0;

        return $prefix . str_pad($lastNum + 1, 6, '0', STR_PAD_LEFT);
    }
}
