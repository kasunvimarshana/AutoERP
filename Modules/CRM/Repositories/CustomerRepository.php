<?php

declare(strict_types=1);

namespace Modules\CRM\Repositories;

use Modules\Core\Repositories\BaseRepository;
use Modules\CRM\Exceptions\CustomerNotFoundException;
use Modules\CRM\Models\Customer;

class CustomerRepository extends BaseRepository
{
    public function __construct(Customer $model)
    {
        parent::__construct($model);
    }

    protected function getModelClass(): string
    {
        return Customer::class;
    }

    protected function getNotFoundExceptionClass(): string
    {
        return CustomerNotFoundException::class;
    }

    public function findByCode(string $code): ?Customer
    {
        return $this->model->where('customer_code', $code)->first();
    }

    public function findByCodeOrFail(string $code): Customer
    {
        $customer = $this->findByCode($code);

        if (! $customer) {
            throw new CustomerNotFoundException("Customer with code {$code} not found");
        }

        return $customer;
    }

    public function findByEmail(string $email): ?Customer
    {
        return $this->model->where('email', $email)->first();
    }
}
