<?php

namespace App\Modules\Billing\Repositories;

use App\Core\BaseRepository;
use App\Modules\Billing\Models\InvoiceItem;

class InvoiceItemRepository extends BaseRepository
{
    public function __construct(InvoiceItem $model)
    {
        parent::__construct($model);
    }

    public function getByInvoice(int $invoiceId)
    {
        return $this->model->where('invoice_id', $invoiceId)->get();
    }

    public function getBySku(int $skuId)
    {
        return $this->model->where('sku_id', $skuId)->get();
    }

    public function getByTenant(int $tenantId)
    {
        return $this->model->where('tenant_id', $tenantId)->get();
    }

    public function deleteByInvoice(int $invoiceId): bool
    {
        return $this->model->where('invoice_id', $invoiceId)->delete();
    }
}
