<?php

namespace App\Modules\CRM\Services;

use App\Core\Services\BaseService;
use App\Modules\CRM\Repositories\CustomerRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Customer Service
 * 
 * Handles business logic for customer operations
 */
class CustomerService extends BaseService
{
    /**
     * Constructor
     *
     * @param CustomerRepository $repository
     */
    public function __construct(CustomerRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Create a new customer
     *
     * @param array $data
     * @return mixed
     */
    public function create(array $data)
    {
        if (empty($data['code'])) {
            $data['code'] = $this->generateCustomerCode();
        }

        return parent::create($data);
    }

    /**
     * Generate unique customer code
     *
     * @return string
     */
    protected function generateCustomerCode(): string
    {
        $prefix = 'CUST-';
        $lastCustomer = $this->repository->model
            ->where('code', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->lockForUpdate()
            ->first();
        
        $number = 1;
        if ($lastCustomer && str_starts_with($lastCustomer->code, $prefix)) {
            $extracted = substr($lastCustomer->code, strlen($prefix));
            if (is_numeric($extracted)) {
                $number = (int)$extracted + 1;
            }
        }
        
        return $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Search customers
     *
     * @param string $search
     * @return Collection
     */
    public function searchCustomers(string $search): Collection
    {
        return $this->repository->searchCustomers($search);
    }

    /**
     * Find customer by code
     *
     * @param string $code
     * @return mixed
     */
    public function findByCode(string $code)
    {
        return $this->repository->findByCode($code);
    }

    /**
     * Find customer by email
     *
     * @param string $email
     * @return mixed
     */
    public function findByEmail(string $email)
    {
        return $this->repository->findByEmail($email);
    }
}
