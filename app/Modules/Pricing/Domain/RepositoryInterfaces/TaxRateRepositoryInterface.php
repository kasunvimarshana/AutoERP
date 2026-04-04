<?php
namespace Modules\Pricing\Domain\RepositoryInterfaces;
use Modules\Pricing\Domain\Entities\TaxRate;

interface TaxRateRepositoryInterface
{
    public function findById(int $id): ?TaxRate;
    public function findByCode(int $tenantId, string $code): ?TaxRate;
    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): mixed;
    public function create(array $data): TaxRate;
    public function update(TaxRate $rate, array $data): TaxRate;
    public function delete(TaxRate $rate): bool;
}
