<?php

namespace App\Modules\Customer\Services;

use App\Core\Services\BaseService;
use App\Modules\Customer\Repositories\CustomerRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerService extends BaseService
{
    /**
     * CustomerService constructor
     */
    public function __construct(CustomerRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Update customer balance
     */
    public function updateBalance(int $id, float $amount, string $type = 'add'): bool
    {
        DB::beginTransaction();

        try {
            $customer = $this->repository->findOrFail($id);
            $currentBalance = $customer->balance ?? 0;

            $newBalance = $type === 'add'
                ? $currentBalance + $amount
                : $currentBalance - $amount;

            $result = $this->repository->update($id, ['balance' => $newBalance]);
            DB::commit();

            Log::info("Customer {$id} balance updated: {$type} {$amount}");

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating customer balance: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get customer credit status
     */
    public function getCreditStatus(int $id): array
    {
        try {
            $customer = $this->repository->findOrFail($id);

            $creditLimit = $customer->credit_limit ?? 0;
            $balance = $customer->balance ?? 0;
            $availableCredit = $creditLimit - $balance;

            $status = 'good';
            if ($balance > $creditLimit) {
                $status = 'over_limit';
            } elseif ($balance > ($creditLimit * 0.9)) {
                $status = 'warning';
            }

            return [
                'customer_id' => $id,
                'credit_limit' => $creditLimit,
                'current_balance' => $balance,
                'available_credit' => max(0, $availableCredit),
                'credit_status' => $status,
                'utilization_percentage' => $creditLimit > 0 ? ($balance / $creditLimit) * 100 : 0,
            ];
        } catch (\Exception $e) {
            Log::error("Error fetching credit status for customer {$id}: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get active customers
     */
    public function getActive()
    {
        try {
            return $this->repository->getActive();
        } catch (\Exception $e) {
            Log::error('Error fetching active customers: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get customers with outstanding balance
     */
    public function getWithOutstandingBalance()
    {
        try {
            return $this->repository->getWithOutstandingBalance();
        } catch (\Exception $e) {
            Log::error('Error fetching customers with outstanding balance: '.$e->getMessage());
            throw $e;
        }
    }
}
