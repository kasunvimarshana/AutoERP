<?php

namespace App\Modules\POS\Services;

use App\Core\BaseService;
use App\Modules\POS\Repositories\POSTransactionRepository;
use App\Modules\POS\Repositories\POSTransactionItemRepository;

class POSTransactionService extends BaseService
{
    protected POSTransactionRepository $repository;
    protected POSTransactionItemRepository $itemRepository;

    public function __construct(
        POSTransactionRepository $repository,
        POSTransactionItemRepository $itemRepository
    ) {
        $this->repository = $repository;
        $this->itemRepository = $itemRepository;
    }

    public function createTransaction(array $data)
    {
        return $this->transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $transaction = $this->repository->create($data);

            if (!empty($items)) {
                foreach ($items as $item) {
                    $item['tenant_id'] = $data['tenant_id'];
                    $item['pos_transaction_id'] = $transaction->id;
                    $this->itemRepository->create($item);
                }
            }

            $this->logActivity('POS Transaction created', ['transaction_id' => $transaction->id]);
            return $transaction->load('items');
        });
    }

    public function updateTransaction(int $id, array $data): bool
    {
        return $this->transaction(function () use ($id, $data) {
            unset($data['items']);

            $result = $this->repository->update($id, $data);
            if ($result) {
                $this->logActivity('POS Transaction updated', ['transaction_id' => $id]);
            }
            return $result;
        });
    }

    public function getTransaction(int $id)
    {
        return $this->repository->with(['items.sku', 'customer', 'cashier', 'tenant'])->find($id);
    }

    public function getAllTransactions()
    {
        return $this->repository->with(['items', 'customer', 'cashier'])->all();
    }

    public function deleteTransaction(int $id): bool
    {
        return $this->transaction(function () use ($id) {
            $result = $this->repository->delete($id);
            if ($result) {
                $this->logActivity('POS Transaction deleted', ['transaction_id' => $id]);
            }
            return $result;
        });
    }

    public function completeTransaction(int $id): bool
    {
        return $this->transaction(function () use ($id) {
            $result = $this->repository->update($id, ['status' => 'completed']);
            if ($result) {
                $this->logActivity('POS Transaction completed', ['transaction_id' => $id]);
            }
            return $result;
        });
    }

    public function cancelTransaction(int $id): bool
    {
        return $this->transaction(function () use ($id) {
            $result = $this->repository->update($id, ['status' => 'cancelled']);
            if ($result) {
                $this->logActivity('POS Transaction cancelled', ['transaction_id' => $id]);
            }
            return $result;
        });
    }

    public function refundTransaction(int $id, ?string $reason = null)
    {
        return $this->transaction(function () use ($id, $reason) {
            $transaction = $this->repository->find($id);

            if (!$transaction) {
                return false;
            }

            if ($transaction->status !== 'completed') {
                throw new \Exception('Only completed transactions can be refunded');
            }

            $updateData = ['status' => 'refunded'];
            if ($reason) {
                $notes = $transaction->notes ? $transaction->notes . "\n" : '';
                $updateData['notes'] = $notes . "Refund reason: " . $reason;
            }

            $result = $this->repository->update($id, $updateData);

            if ($result) {
                $this->logActivity('POS Transaction refunded', [
                    'transaction_id' => $id,
                    'reason' => $reason,
                    'refund_amount' => $transaction->total_amount,
                ]);
            }

            return $result;
        });
    }

    public function getByStatus(string $status)
    {
        return $this->repository->getByStatus($status);
    }

    public function getByCustomer(int $customerId)
    {
        return $this->repository->getByCustomer($customerId);
    }

    public function getByCashier(int $cashierId)
    {
        return $this->repository->getByCashier($cashierId);
    }

    public function getByDateRange(string $startDate, string $endDate)
    {
        return $this->repository->getByDateRange($startDate, $endDate);
    }

    public function getTotalSales(int $tenantId, ?string $startDate = null, ?string $endDate = null)
    {
        return $this->repository->getTotalSales($tenantId, $startDate, $endDate);
    }

    public function getSalesByPaymentMethod(int $tenantId, ?string $startDate = null, ?string $endDate = null)
    {
        return $this->repository->getSalesByPaymentMethod($tenantId, $startDate, $endDate);
    }
}
