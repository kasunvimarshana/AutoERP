<?php

namespace App\Modules\Invoice\Repositories;

use App\Base\BaseRepository;
use App\Modules\Invoice\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Collection;

class InvoiceItemRepository extends BaseRepository
{
    public function __construct(InvoiceItem $model)
    {
        parent::__construct($model);
    }

    public function findByInvoice(string $invoiceId, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('invoice_id', $invoiceId)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function findByProduct(string $productVariantId, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('product_variant_id', $productVariantId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
