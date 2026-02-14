<?php

namespace App\Modules\Billing\Services;

use App\Core\BaseService;
use App\Modules\Billing\Repositories\InvoiceItemRepository;

class InvoiceItemService extends BaseService
{
    protected InvoiceItemRepository $repository;

    public function __construct(InvoiceItemRepository $repository)
    {
        $this->repository = $repository;
    }

    public function createInvoiceItem(array $data)
    {
        return $this->transaction(function () use ($data) {
            $item = $this->repository->create($data);
            $this->logActivity('Invoice item created', ['item_id' => $item->id]);
            return $item;
        });
    }

    public function updateInvoiceItem(int $id, array $data): bool
    {
        return $this->transaction(function () use ($id, $data) {
            $result = $this->repository->update($id, $data);
            if ($result) {
                $this->logActivity('Invoice item updated', ['item_id' => $id]);
            }
            return $result;
        });
    }

    public function getInvoiceItem(int $id)
    {
        return $this->repository->with(['invoice', 'sku', 'tenant'])->find($id);
    }

    public function getAllInvoiceItems()
    {
        return $this->repository->with(['invoice', 'sku'])->all();
    }

    public function deleteInvoiceItem(int $id): bool
    {
        return $this->transaction(function () use ($id) {
            $result = $this->repository->delete($id);
            if ($result) {
                $this->logActivity('Invoice item deleted', ['item_id' => $id]);
            }
            return $result;
        });
    }

    public function getItemsByInvoice(int $invoiceId)
    {
        return $this->repository->getByInvoice($invoiceId);
    }
}
