<?php

namespace App\Modules\InvoicingManagement\Repositories;

use App\Core\Base\BaseRepository;
use App\Modules\InvoicingManagement\Models\InvoiceItem;

class InvoiceItemRepository extends BaseRepository
{
    public function __construct(InvoiceItem $model)
    {
        parent::__construct($model);
    }

    /**
     * Search invoice items by various criteria
     */
    public function search(array $criteria)
    {
        $query = $this->model->query();

        if (!empty($criteria['search'])) {
            $search = $criteria['search'];
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('item_type', 'like', "%{$search}%");
            });
        }

        if (!empty($criteria['invoice_id'])) {
            $query->where('invoice_id', $criteria['invoice_id']);
        }

        if (!empty($criteria['item_type'])) {
            $query->where('item_type', $criteria['item_type']);
        }

        if (!empty($criteria['tenant_id'])) {
            $query->where('tenant_id', $criteria['tenant_id']);
        }

        return $query->with(['invoice'])
            ->orderBy('created_at', 'desc')
            ->paginate($criteria['per_page'] ?? 15);
    }

    /**
     * Get items for invoice
     */
    public function getForInvoice(int $invoiceId)
    {
        return $this->model->where('invoice_id', $invoiceId)->get();
    }

    /**
     * Get items by type
     */
    public function getByType(string $type)
    {
        return $this->model->where('item_type', $type)->with(['invoice'])->get();
    }

    /**
     * Get labor items
     */
    public function getLaborItems()
    {
        return $this->model->where('item_type', 'labor')->with(['invoice'])->get();
    }

    /**
     * Get parts items
     */
    public function getPartsItems()
    {
        return $this->model->where('item_type', 'part')->with(['invoice'])->get();
    }

    /**
     * Get service items
     */
    public function getServiceItems()
    {
        return $this->model->where('item_type', 'service')->with(['invoice'])->get();
    }
}
