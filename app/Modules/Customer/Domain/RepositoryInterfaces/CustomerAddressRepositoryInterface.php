<?php
namespace Modules\Customer\Domain\RepositoryInterfaces;

use Modules\Customer\Domain\Entities\CustomerAddress;

interface CustomerAddressRepositoryInterface
{
    public function findById(int $id): ?CustomerAddress;
    public function findByCustomer(int $customerId): array;
    public function create(array $data): CustomerAddress;
    public function update(CustomerAddress $address, array $data): CustomerAddress;
    public function delete(CustomerAddress $address): bool;
}
