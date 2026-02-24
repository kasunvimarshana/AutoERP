<?php
namespace Modules\CRM\Domain\Contracts;
interface ActivityRepositoryInterface
{
    public function findById(string $id): ?object;
    public function findByRelated(string $relatedType, string $relatedId): object;
    public function paginate(array $filters, int $perPage = 15): object;
    public function create(array $data): object;
    public function update(string $id, array $data): object;
    public function delete(string $id): bool;
}
