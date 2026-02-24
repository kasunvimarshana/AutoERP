<?php
namespace Modules\CRM\Domain\Contracts;
interface OpportunityRepositoryInterface
{
    public function findById(string $id): ?object;
    public function paginate(array $filters, int $perPage = 15): object;
    public function findByStage(string $stage): object;
    public function create(array $data): object;
    public function update(string $id, array $data): object;
    public function delete(string $id): bool;
}
