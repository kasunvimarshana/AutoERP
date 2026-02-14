<?php

namespace App\Modules\POS\Services;

use App\Core\BaseService;
use App\Modules\POS\Repositories\POSTransactionItemRepository;

class POSTransactionItemService extends BaseService
{
    protected POSTransactionItemRepository $repository;

    public function __construct(POSTransactionItemRepository $repository)
    {
        $this->repository = $repository;
    }

    public function createItem(array $data)
    {
        return $this->transaction(function () use ($data) {
            $item = $this->repository->create($data);
            $this->logActivity('POS Transaction Item created', ['item_id' => $item->id]);
            return $item;
        });
    }

    public function updateItem(int $id, array $data): bool
    {
        return $this->transaction(function () use ($id, $data) {
            $result = $this->repository->update($id, $data);
            if ($result) {
                $this->logActivity('POS Transaction Item updated', ['item_id' => $id]);
            }
            return $result;
        });
    }

    public function getItem(int $id)
    {
        return $this->repository->with(['sku', 'posTransaction', 'tenant'])->find($id);
    }

    public function getAllItems()
    {
        return $this->repository->with(['sku', 'posTransaction'])->all();
    }

    public function deleteItem(int $id): bool
    {
        return $this->transaction(function () use ($id) {
            $result = $this->repository->delete($id);
            if ($result) {
                $this->logActivity('POS Transaction Item deleted', ['item_id' => $id]);
            }
            return $result;
        });
    }

    public function getByTransaction(int $transactionId)
    {
        return $this->repository->getByTransaction($transactionId);
    }

    public function getBySKU(int $skuId)
    {
        return $this->repository->getBySKU($skuId);
    }

    public function getTopSellingProducts(int $tenantId, int $limit = 10)
    {
        return $this->repository->getTopSellingProducts($tenantId, $limit);
    }
}
