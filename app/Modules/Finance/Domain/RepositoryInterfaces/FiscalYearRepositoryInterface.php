<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Finance\Domain\Entities\FiscalYear;

interface FiscalYearRepositoryInterface extends RepositoryInterface
{
    public function save(FiscalYear $fiscalYear): FiscalYear;

    public function findByTenantAndName(int $tenantId, string $name): ?FiscalYear;
}
