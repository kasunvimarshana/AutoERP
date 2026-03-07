<?php

namespace App\Modules\Inventory\Repositories;

use App\Modules\Inventory\DTOs\InventoryDTO;
use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Inventory\Repositories\Interfaces\InventoryRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class InventoryRepository implements InventoryRepositoryInterface
{
    public function __construct(private InventoryItem $model) {}

    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (isset($filters['low_stock']) && $filters['low_stock']) {
            $query->whereColumn('quantity', '<=', 'minimum_quantity');
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $allowedSorts = ['quantity', 'product_name', 'created_at', 'updated_at', 'status'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, in_array($sortDir, ['asc', 'desc']) ? $sortDir : 'desc');
        }

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?InventoryItem
    {
        return $this->model->find($id);
    }

    public function findByProductId(int $productId): ?InventoryItem
    {
        return $this->model->where('product_id', $productId)->first();
    }

    public function findByProductSku(string $sku): ?InventoryItem
    {
        return $this->model->where('product_sku', $sku)->first();
    }

    public function create(InventoryDTO $dto): InventoryItem
    {
        return $this->model->create($dto->toArray());
    }

    public function update(int $id, array $data): ?InventoryItem
    {
        $item = $this->findById($id);
        if (!$item) {
            return null;
        }
        $item->update($data);
        return $item->fresh();
    }

    public function delete(int $id): bool
    {
        $item = $this->findById($id);
        if (!$item) {
            return false;
        }
        return (bool) $item->delete();
    }

    public function adjustStock(int $id, int $quantityChange): ?InventoryItem
    {
        $item = $this->findById($id);
        if (!$item) {
            return null;
        }

        $newQuantity = max(0, $item->quantity + $quantityChange);
        $status = $this->calculateStatus($newQuantity, $item->reserved_quantity, $item->minimum_quantity);

        $item->update([
            'quantity' => $newQuantity,
            'status' => $status,
            'last_restock_at' => $quantityChange > 0 ? now() : $item->last_restock_at,
        ]);

        return $item->fresh();
    }

    public function reserveStock(int $id, int $quantity): bool
    {
        $item = $this->findById($id);
        if (!$item) {
            return false;
        }

        $available = $item->quantity - $item->reserved_quantity;
        if ($available < $quantity) {
            return false;
        }

        return $item->update([
            'reserved_quantity' => $item->reserved_quantity + $quantity,
            'status' => $this->calculateStatus($item->quantity, $item->reserved_quantity + $quantity, $item->minimum_quantity),
        ]);
    }

    public function releaseStock(int $id, int $quantity): bool
    {
        $item = $this->findById($id);
        if (!$item) {
            return false;
        }

        $newReserved = max(0, $item->reserved_quantity - $quantity);
        return $item->update([
            'reserved_quantity' => $newReserved,
            'status' => $this->calculateStatus($item->quantity, $newReserved, $item->minimum_quantity),
        ]);
    }

    private function calculateStatus(int $quantity, int $reservedQuantity, int $minimumQuantity): string
    {
        $available = $quantity - $reservedQuantity;
        if ($available <= 0) {
            return InventoryItem::STATUS_OUT_OF_STOCK;
        }
        if ($quantity <= $minimumQuantity) {
            return InventoryItem::STATUS_LOW_STOCK;
        }
        return InventoryItem::STATUS_AVAILABLE;
    }
}
