<?php

declare(strict_types=1);

namespace Modules\Financial\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Financial\Domain\Contracts\Repositories\FiscalYearRepositoryInterface;
use Modules\Financial\Infrastructure\Persistence\Eloquent\Models\FiscalYearModel;

class EloquentFiscalYearRepository extends EloquentRepository implements FiscalYearRepositoryInterface
{
    public function __construct(FiscalYearModel $model)
    {
        parent::__construct($model);
    }

    /**
     * Find the currently open fiscal year.
     */
    public function findOpen(): mixed
    {
        return $this->model->newQuery()->where('status', 'open')->first();
    }
}
