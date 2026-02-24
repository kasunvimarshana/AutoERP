<?php
namespace Modules\Purchase\Domain\Contracts;
interface PurchaseOrderRepositoryInterface
{
    public function findById(string $id): ?object;
    public function paginate(array $filters, int $perPage = 15): object;
    public function create(array $data): object;
    public function update(string $id, array $data): object;
    public function delete(string $id): bool;
    public function nextNumber(string $tenantId): string;
}
