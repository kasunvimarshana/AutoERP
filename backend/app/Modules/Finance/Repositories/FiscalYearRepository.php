<?php

namespace App\Modules\Finance\Repositories;

use App\Modules\Finance\Models\FiscalYear;
use App\Repositories\BaseRepository;

/**
 * Fiscal Year Repository
 *
 * Handles data access for fiscal years.
 */
class FiscalYearRepository extends BaseRepository
{
    protected function model(): string
    {
        return FiscalYear::class;
    }

    public function getOpenFiscalYears()
    {
        return $this->model
            ->open()
            ->orderBy('start_date', 'desc')
            ->get();
    }

    public function getClosedFiscalYears()
    {
        return $this->model
            ->closed()
            ->orderBy('start_date', 'desc')
            ->get();
    }

    public function getCurrentFiscalYear(): ?FiscalYear
    {
        return $this->model
            ->current()
            ->first();
    }

    public function findByDateRange(string $startDate, string $endDate): ?FiscalYear
    {
        return $this->model
            ->where('start_date', $startDate)
            ->where('end_date', $endDate)
            ->first();
    }
}
