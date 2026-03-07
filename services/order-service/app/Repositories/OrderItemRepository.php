<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\OrderItem;
use App\Repositories\Interfaces\OrderItemRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class OrderItemRepository implements OrderItemRepositoryInterface
{
    public function __construct(private readonly OrderItem $model) {}

    public function getByOrderId(int $orderId): Collection
    {
        return $this->model->newQuery()
            ->byOrder($orderId)
            ->get();
    }

    public function findById(int $id): ?OrderItem
    {
        return $this->model->newQuery()->find($id);
    }

    public function create(array $data): OrderItem
    {
        return $this->model->newQuery()->create($data);
    }

    public function createMany(int $orderId, array $items): Collection
    {
        $created = new Collection();

        foreach ($items as $itemData) {
            $itemData['order_id'] = $orderId;
            $created->push($this->create($itemData));
        }

        return $created;
    }

    public function update(int $id, array $data): ?OrderItem
    {
        $item = $this->findById($id);

        if ($item === null) {
            return null;
        }

        $item->update($data);

        return $item->fresh();
    }

    public function deleteByOrderId(int $orderId): int
    {
        return $this->model->newQuery()
            ->where('order_id', $orderId)
            ->delete();
    }
}
