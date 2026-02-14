<?php

namespace App\Modules\POS\Repositories;

use App\Core\BaseRepository;
use App\Modules\POS\Models\POSTransactionItem;

class POSTransactionItemRepository extends BaseRepository
{
    public function __construct(POSTransactionItem $model)
    {
        parent::__construct($model);
    }

    public function getByTransaction(int $transactionId)
    {
        return $this->model->where('pos_transaction_id', $transactionId)->get();
    }

    public function getByTenant(int $tenantId)
    {
        return $this->model->where('tenant_id', $tenantId)->get();
    }

    public function getBySKU(int $skuId)
    {
        return $this->model->where('sku_id', $skuId)->get();
    }

    public function getTopSellingProducts(int $tenantId, int $limit = 10)
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->selectRaw('sku_id, SUM(quantity) as total_quantity, SUM(line_total) as total_revenue')
            ->groupBy('sku_id')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get();
    }
}
