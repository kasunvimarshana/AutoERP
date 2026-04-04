<?php
namespace Modules\Pricing\Domain\RepositoryInterfaces;
use Modules\Pricing\Domain\Entities\TaxGroup;

interface TaxGroupRepositoryInterface
{
    public function findById(int $id): ?TaxGroup;
    public function findByCode(int $tenantId, string $code): ?TaxGroup;
    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): mixed;
    public function create(array $data): TaxGroup;
    public function update(TaxGroup $group, array $data): TaxGroup;
    public function delete(TaxGroup $group): bool;
}
