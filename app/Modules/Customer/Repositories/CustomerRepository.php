<?php

namespace App\Modules\Customer\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Customer\Models\Customer;
use Illuminate\Database\Eloquent\Collection;

class CustomerRepository extends BaseRepository
{
    /**
     * Specify Model class name
     */
    protected function model(): string
    {
        return Customer::class;
    }

    /**
     * Get active customers
     */
    public function getActive(): Collection
    {
        return $this->model->where('is_active', true)->get();
    }

    /**
     * Find customer by email
     */
    public function findByEmail(string $email): ?Customer
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Get customers with outstanding balance
     */
    public function getWithOutstandingBalance(): Collection
    {
        return $this->model->where('balance', '>', 0)->get();
    }

    /**
     * Get customers by credit status
     */
    public function getByCreditStatus(string $status): Collection
    {
        return $this->model->where('credit_status', $status)->get();
    }
}
