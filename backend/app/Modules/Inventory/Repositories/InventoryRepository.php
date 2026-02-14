<?php

namespace App\Modules\Inventory\Repositories;

use App\Base\BaseRepository;
use App\Modules\Inventory\Models\Inventory;
use Illuminate\Database\Eloquent\Collection;

class InventoryRepository extends BaseRepository
{
    public function __construct(Inventory $model)
    {
        parent::__construct($model);
    }

    public function findByVariantAndBranch(string $variantId, string $branchId): ?Inventory
    {
        return $this->model
            ->where('product_variant_id', $variantId)
            ->where('branch_id', $branchId)
            ->first();
    }

    public function getLowStock(string $branchId, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('branch_id', $branchId)
            ->whereColumn('quantity_on_hand', '<=', 'reorder_level')
            ->where('reorder_level', '>', 0)
            ->get();
    }

    public function getByBranch(string $branchId, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('branch_id', $branchId)
            ->get();
    }

    public function getByTenant(string $tenantId, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('tenant_id', $tenantId)
            ->get();
    }

    public function getByVariant(string $variantId, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('product_variant_id', $variantId)
            ->get();
    }

    public function createOrUpdate(array $data): Inventory
    {
        return $this->model->updateOrCreate(
            [
                'product_variant_id' => $data['product_variant_id'],
                'branch_id' => $data['branch_id'],
            ],
            $data
        );
    }
}
