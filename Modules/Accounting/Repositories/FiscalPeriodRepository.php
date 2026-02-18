<?php

declare(strict_types=1);

namespace Modules\Accounting\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Accounting\Entities\FiscalPeriod;
use Modules\Accounting\Repositories\Contracts\FiscalPeriodRepositoryInterface;
use Modules\Core\Repositories\BaseRepository;

/**
 * Fiscal Period Repository Implementation
 *
 * Handles all fiscal period data access operations.
 */
class FiscalPeriodRepository extends BaseRepository implements FiscalPeriodRepositoryInterface
{
    /**
     * FiscalPeriodRepository constructor.
     */
    public function __construct(FiscalPeriod $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function getByFiscalYear(int $fiscalYear): Collection
    {
        return $this->model->where('fiscal_year', $fiscalYear)->orderBy('start_date')->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->orderBy('start_date')->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getOpenPeriods(): Collection
    {
        return $this->model->where('status', FiscalPeriod::STATUS_OPEN)->orderBy('start_date')->get();
    }

    /**
     * {@inheritdoc}
     */
    public function findByDate(\DateTimeInterface $date): ?FiscalPeriod
    {
        return $this->model
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentPeriod(): ?FiscalPeriod
    {
        return $this->findByDate(now());
    }
}
