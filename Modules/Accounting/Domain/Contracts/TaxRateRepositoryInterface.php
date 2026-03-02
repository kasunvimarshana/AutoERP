<?php

declare(strict_types=1);

namespace Modules\Accounting\Domain\Contracts;

use Modules\Accounting\Domain\Entities\TaxRate;

interface TaxRateRepositoryInterface
{
    /** @return TaxRate[] */
    public function findAll(int $tenantId): array;

    public function findById(int $id, int $tenantId): ?TaxRate;

    public function save(TaxRate $taxRate): TaxRate;

    public function delete(int $id, int $tenantId): void;
}
