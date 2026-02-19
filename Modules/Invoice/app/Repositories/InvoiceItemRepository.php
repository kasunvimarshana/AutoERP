<?php

declare(strict_types=1);

namespace Modules\Invoice\Repositories;

use App\Core\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Invoice\Models\InvoiceItem;

/**
 * InvoiceItem Repository
 *
 * Handles data access for InvoiceItem model
 */
class InvoiceItemRepository extends BaseRepository
{
    /**
     * {@inheritDoc}
     */
    protected function makeModel(): Model
    {
        return new InvoiceItem;
    }

    /**
     * Get items for invoice
     */
    public function getForInvoice(int $invoiceId): Collection
    {
        return $this->model->newQuery()->where('invoice_id', $invoiceId)->get();
    }

    /**
     * Get items by type
     */
    public function getByType(int $invoiceId, string $type): Collection
    {
        return $this->model->newQuery()
            ->where('invoice_id', $invoiceId)
            ->where('item_type', $type)
            ->get();
    }

    /**
     * Delete items for invoice
     */
    public function deleteForInvoice(int $invoiceId): bool
    {
        return $this->model->newQuery()->where('invoice_id', $invoiceId)->delete();
    }
}
