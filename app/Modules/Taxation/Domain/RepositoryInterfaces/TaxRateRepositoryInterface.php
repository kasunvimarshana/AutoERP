<?php

declare(strict_types=1);

namespace Modules\Taxation\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Taxation\Domain\Entities\TaxRate;

interface TaxRateRepositoryInterface extends RepositoryInterface
{
    public function save(TaxRate $taxRate): TaxRate;

    public function findByCode(int $tenantId, string $code): ?TaxRate;

    public function findByJurisdiction(int $tenantId, string $jurisdiction): Collection;

    public function findByType(int $tenantId, string $taxType): Collection;
}
