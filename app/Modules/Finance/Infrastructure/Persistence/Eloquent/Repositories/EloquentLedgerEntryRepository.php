<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Finance\Application\Repositories\LedgerEntryRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\LedgerEntryModel;

final class EloquentLedgerEntryRepository extends FinanceRepository implements LedgerEntryRepositoryInterface
{
    public function __construct(LedgerEntryModel $model)
    {
        parent::__construct($model);
    }
}
