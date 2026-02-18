<?php

declare(strict_types=1);

namespace Modules\Accounting\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Accounting\Entities\InvoiceLine;
use Modules\Accounting\Repositories\Contracts\InvoiceLineRepositoryInterface;
use Modules\Core\Repositories\BaseRepository;

/**
 * Invoice Line Repository Implementation
 *
 * Handles all invoice line data access operations.
 */
class InvoiceLineRepository extends BaseRepository implements InvoiceLineRepositoryInterface
{
    /**
     * InvoiceLineRepository constructor.
     */
    public function __construct(InvoiceLine $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function getByInvoice(int $invoiceId): Collection
    {
        return $this->model->where('invoice_id', $invoiceId)->orderBy('sort_order')->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getByProduct(int $productId): Collection
    {
        return $this->model->where('product_id', $productId)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getByAccount(int $accountId): Collection
    {
        return $this->model->where('account_id', $accountId)->get();
    }
}
