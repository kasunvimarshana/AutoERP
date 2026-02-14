<?php

namespace App\Modules\Inventory\Repositories;

use App\Core\BaseRepository;
use App\Modules\Inventory\Models\StockMovement;

class StockMovementRepository extends BaseRepository
{
    public function __construct(StockMovement $model)
    {
        parent::__construct($model);
    }

    public function getByTenant(int $tenantId)
    {
        return $this->model->where('tenant_id', $tenantId)->orderBy('created_at', 'desc')->get();
    }

    public function getBySKU(int $skuId)
    {
        return $this->model->where('sku_id', $skuId)->orderBy('created_at', 'desc')->get();
    }

    public function getByBatch(int $batchId)
    {
        return $this->model->where('batch_id', $batchId)->orderBy('created_at', 'desc')->get();
    }

    public function getByType(string $type)
    {
        return $this->model->where('type', $type)->orderBy('created_at', 'desc')->get();
    }

    public function getByReference(string $referenceType, int $referenceId)
    {
        return $this->model->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getByCreator(int $creatorId)
    {
        return $this->model->where('created_by', $creatorId)->orderBy('created_at', 'desc')->get();
    }

    public function getLatestBalance(int $skuId): int
    {
        $latest = $this->model->where('sku_id', $skuId)->orderBy('created_at', 'desc')->first();
        return $latest ? $latest->balance_after : 0;
    }
}
