<?php

declare(strict_types=1);

namespace Modules\Accounting\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Accounting\Entities\FiscalPeriod;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;

/**
 * Fiscal Period Repository Interface
 *
 * Defines the contract for fiscal period data access operations.
 */
interface FiscalPeriodRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get periods by fiscal year.
     */
    public function getByFiscalYear(int $fiscalYear): Collection;

    /**
     * Get periods by status.
     */
    public function getByStatus(string $status): Collection;

    /**
     * Get open periods.
     */
    public function getOpenPeriods(): Collection;

    /**
     * Find period by date.
     */
    public function findByDate(\DateTimeInterface $date): ?FiscalPeriod;

    /**
     * Get current period.
     */
    public function getCurrentPeriod(): ?FiscalPeriod;
}
