<?php
namespace Modules\CRM\Domain\Contracts;
interface LeadRepositoryInterface
{
    public function findById(string $id): ?object;
    public function findByEmail(string $email): ?object;
    public function paginate(array $filters, int $perPage = 15): object;
    public function create(array $data): object;
    public function update(string $id, array $data): object;
    public function delete(string $id): bool;
}
