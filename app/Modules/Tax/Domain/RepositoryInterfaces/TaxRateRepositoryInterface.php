<?php

declare(strict_types=1);

namespace Modules\Tax\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Tax\Domain\Entities\TaxRate;

interface TaxRateRepositoryInterface extends RepositoryInterface
{
    public function save(TaxRate $taxRate): TaxRate;

    public function findByTenantGroupAndName(int $tenantId, int $taxGroupId, string $name): ?TaxRate;

    /**
     * @return list<TaxRate>
     */
    public function findActiveByGroup(int $tenantId, int $taxGroupId, \DateTimeInterface $onDate): array;

    public function find(int|string $id, array $columns = ['*']): ?TaxRate;
}
