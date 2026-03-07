<?php
namespace App\Modules\Inventory\Services;

use App\Helpers\PaginationHelper;
use App\Interfaces\MessageBrokerInterface;
use App\Modules\Inventory\Repositories\InventoryRepository;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function __construct(
        private InventoryRepository $inventoryRepository,
        private MessageBrokerInterface $messageBroker,
    ) {}

    public function listInventory(array $filters, int $tenantId): mixed
    {
        $filters['tenant_id'] = $tenantId;
        ['per_page' => $perPage, 'page' => $page] = PaginationHelper::fromRequest(request());

        $query = $this->inventoryRepository->all($filters, ['product']);
        return PaginationHelper::paginate($query, $perPage, $page);
    }

    public function getInventory(int $id): mixed
    {
        return $this->inventoryRepository->find($id, ['product', 'product.tenant']);
    }

    public function createInventory(array $data, int $tenantId): mixed
    {
        return DB::transaction(function () use ($data, $tenantId) {
            $data['tenant_id'] = $tenantId;
            $inventory = $this->inventoryRepository->create($data);

            $this->messageBroker->publish('inventory.created', [
                'inventory_id' => $inventory->id,
                'product_id' => $inventory->product_id,
                'tenant_id' => $tenantId,
            ]);

            return $inventory->load('product');
        });
    }

    public function updateInventory(int $id, array $data): mixed
    {
        return DB::transaction(function () use ($id, $data) {
            $inventory = $this->inventoryRepository->update($id, $data);

            $this->messageBroker->publish('inventory.updated', [
                'inventory_id' => $inventory->id,
                'tenant_id' => $inventory->tenant_id,
            ]);

            return $inventory->load('product');
        });
    }

    public function deleteInventory(int $id): bool
    {
        $inventory = $this->inventoryRepository->find($id);
        $result = $this->inventoryRepository->delete($id);

        if ($result) {
            $this->messageBroker->publish('inventory.deleted', [
                'inventory_id' => $id,
                'tenant_id' => $inventory->tenant_id,
            ]);
        }

        return $result;
    }

    public function adjustStock(int $id, int $delta, string $reason = ''): mixed
    {
        return DB::transaction(function () use ($id, $delta, $reason) {
            $inventory = $this->inventoryRepository->adjustQuantity($id, $delta);

            $this->messageBroker->publish('inventory.stock_adjusted', [
                'inventory_id' => $id,
                'delta' => $delta,
                'reason' => $reason,
                'new_quantity' => $inventory->quantity,
            ]);

            return $inventory;
        });
    }
}
