<?php
namespace Modules\Supplier\Domain\RepositoryInterfaces;

use Modules\Supplier\Domain\Entities\SupplierContact;

interface SupplierContactRepositoryInterface
{
    public function findById(int $id): ?SupplierContact;
    public function findBySupplier(int $supplierId): array;
    public function create(array $data): SupplierContact;
    public function update(SupplierContact $contact, array $data): SupplierContact;
    public function delete(SupplierContact $contact): bool;
}
