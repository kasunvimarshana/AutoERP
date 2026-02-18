<?php

namespace App\Modules\Manufacturing\Repositories;

use App\Modules\Manufacturing\Models\BillOfMaterial;
use App\Repositories\BaseRepository;

/**
 * Bill of Material Repository
 *
 * Handles data access for bill of materials.
 */
class BillOfMaterialRepository extends BaseRepository
{
    /**
     * Specify Model class name
     */
    protected function model(): string
    {
        return BillOfMaterial::class;
    }

    /**
     * Find BOM by BOM number
     */
    public function findByBomNumber(string $bomNumber): ?BillOfMaterial
    {
        return $this->model->where('bom_number', $bomNumber)->first();
    }

    /**
     * Get active BOMs for a product
     */
    public function getActiveForProduct(int $productId)
    {
        return $this->model
            ->forProduct($productId)
            ->active()
            ->with(['items.componentProduct', 'items.uom'])
            ->orderBy('version', 'desc')
            ->get();
    }

    /**
     * Get latest active BOM for a product
     */
    public function getLatestActiveForProduct(int $productId): ?BillOfMaterial
    {
        return $this->model
            ->forProduct($productId)
            ->active()
            ->with(['items.componentProduct', 'items.uom'])
            ->orderBy('version', 'desc')
            ->first();
    }

    /**
     * Find with items loaded
     */
    public function findWithItems(int $id): BillOfMaterial
    {
        return $this->model->with(['product', 'items.componentProduct', 'items.uom'])->findOrFail($id);
    }

    /**
     * Get all BOMs for a product
     */
    public function getAllForProduct(int $productId)
    {
        return $this->model
            ->forProduct($productId)
            ->with(['items.componentProduct', 'items.uom'])
            ->orderBy('version', 'desc')
            ->get();
    }

    /**
     * Search BOMs
     */
    public function search(string $search)
    {
        return $this->model
            ->where(function ($query) use ($search) {
                $query->where('bom_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%");
                    });
            })
            ->with(['product', 'items.componentProduct'])
            ->get();
    }
}
