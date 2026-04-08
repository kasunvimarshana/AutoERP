<?php

declare(strict_types=1);

namespace Modules\Tax\Domain\RepositoryInterfaces;

use Modules\Tax\Domain\Entities\TaxGroupRate;

interface TaxGroupRateRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?TaxGroupRate;

    /** @return TaxGroupRate[] */
    public function findByTaxGroup(string $tenantId, string $taxGroupId): array;

    public function save(TaxGroupRate $rate): void;

    public function delete(string $tenantId, string $id): void;
}
