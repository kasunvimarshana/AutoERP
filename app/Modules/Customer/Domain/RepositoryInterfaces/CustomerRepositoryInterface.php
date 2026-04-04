<?php
namespace Modules\Customer\Domain\RepositoryInterfaces;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Customer\Domain\Entities\Customer;

interface CustomerRepositoryInterface
{
    public function findById(int $id): ?Customer;
    public function findByCode(int $tenantId, string $code): ?Customer;
    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function create(array $data): Customer;
    public function update(Customer $customer, array $data): Customer;
    public function delete(Customer $customer): bool;
}
