<?php

namespace App\Modules\Inventory\Repositories;

use App\Core\BaseRepository;
use App\Modules\Inventory\Models\Batch;

class BatchRepository extends BaseRepository
{
    public function __construct(Batch $model)
    {
        parent::__construct($model);
    }

    public function findByBatchNumber(string $batchNumber): ?Batch
    {
        return $this->findBy('batch_number', $batchNumber);
    }

    public function getByTenant(int $tenantId)
    {
        return $this->model->where('tenant_id', $tenantId)->get();
    }

    public function getBySKU(int $skuId)
    {
        return $this->model->where('sku_id', $skuId)->get();
    }

    public function getByStatus(string $status)
    {
        return $this->model->where('status', $status)->get();
    }

    public function getExpiredBatches()
    {
        return $this->model->where('expiry_date', '<', now())->get();
    }

    public function getExpiringBatches(int $days = 30)
    {
        return $this->model->whereBetween('expiry_date', [now(), now()->addDays($days)])->get();
    }
}
