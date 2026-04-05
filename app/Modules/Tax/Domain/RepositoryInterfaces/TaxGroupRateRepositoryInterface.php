<?php
declare(strict_types=1);
namespace Modules\Tax\Domain\RepositoryInterfaces;

use Modules\Tax\Domain\Entities\TaxGroupRate;

interface TaxGroupRateRepositoryInterface
{
    public function findById(int $id): ?TaxGroupRate;
    public function findByTaxGroup(int $taxGroupId): array; // ordered by sort_order
    public function create(array $data): TaxGroupRate;
    public function update(int $id, array $data): ?TaxGroupRate;
    public function delete(int $id): bool;
    public function deleteByTaxGroup(int $taxGroupId): void;
}
