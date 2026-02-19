<?php

declare(strict_types=1);

namespace Modules\Accounting\Repositories;

use Modules\Accounting\Enums\FiscalPeriodStatus;
use Modules\Accounting\Exceptions\FiscalPeriodNotFoundException;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Core\Repositories\BaseRepository;

class FiscalPeriodRepository extends BaseRepository
{
    public function __construct(FiscalPeriod $model)
    {
        parent::__construct($model);
    }

    protected function getModelClass(): string
    {
        return FiscalPeriod::class;
    }

    protected function getNotFoundExceptionClass(): string
    {
        return FiscalPeriodNotFoundException::class;
    }

    public function findByCode(string $code): ?FiscalPeriod
    {
        return $this->model->where('code', $code)->first();
    }

    public function findByCodeOrFail(string $code): FiscalPeriod
    {
        $period = $this->findByCode($code);

        if (! $period) {
            throw new FiscalPeriodNotFoundException("Fiscal period with code {$code} not found");
        }

        return $period;
    }

    public function getByStatus(FiscalPeriodStatus $status, int $perPage = 15)
    {
        return $this->model->where('status', $status)->latest('start_date')->paginate($perPage);
    }

    public function getByFiscalYear(string $fiscalYearId, int $perPage = 15)
    {
        return $this->model->where('fiscal_year_id', $fiscalYearId)->orderBy('start_date')->paginate($perPage);
    }

    public function findByDate(string $date): ?FiscalPeriod
    {
        return $this->model
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();
    }

    public function getOpenPeriods()
    {
        return $this->model->where('status', FiscalPeriodStatus::Open)->get();
    }

    public function getClosedPeriods()
    {
        return $this->model->where('status', FiscalPeriodStatus::Closed)->get();
    }

    public function getLockedPeriods()
    {
        return $this->model->where('status', FiscalPeriodStatus::Locked)->get();
    }

    public function getCurrentPeriod(): ?FiscalPeriod
    {
        return $this->findByDate(now()->toDateString());
    }
}
