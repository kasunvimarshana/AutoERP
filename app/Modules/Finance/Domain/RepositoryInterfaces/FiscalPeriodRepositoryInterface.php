<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Finance\Domain\Entities\FiscalPeriod;

interface FiscalPeriodRepositoryInterface extends RepositoryInterface
{
    public function save(FiscalPeriod $fiscalPeriod): FiscalPeriod;

    public function findOpenPeriodForDate(int $tenantId, \DateTimeInterface $date): ?FiscalPeriod;

    public function findByTenantAndYearAndPeriodNumber(int $tenantId, int $fiscalYearId, int $periodNumber): ?FiscalPeriod;
}
