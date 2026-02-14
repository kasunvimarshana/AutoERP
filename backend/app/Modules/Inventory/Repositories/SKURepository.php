<?php

namespace App\Modules\Inventory\Repositories;

use App\Core\BaseRepository;
use App\Modules\Inventory\Models\SKU;

class SKURepository extends BaseRepository
{
    public function __construct(SKU $model)
    {
        parent::__construct($model);
    }

    public function findBySKUCode(string $skuCode): ?SKU
    {
        return $this->findBy('sku_code', $skuCode);
    }

    public function findByBarcode(string $barcode): ?SKU
    {
        return $this->findBy('barcode', $barcode);
    }

    public function getByTenant(int $tenantId)
    {
        return $this->model->where('tenant_id', $tenantId)->get();
    }

    public function getByProduct(int $productId)
    {
        return $this->model->where('product_id', $productId)->get();
    }

    public function getByStatus(string $status)
    {
        return $this->model->where('status', $status)->get();
    }

    public function getBelowReorderLevel()
    {
        return $this->model->whereRaw('current_stock <= reorder_level')->get();
    }
}
