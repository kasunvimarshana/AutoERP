<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Finance\Application\Repositories\JournalEntryLineRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\JournalEntryLineModel;

final class EloquentJournalEntryLineRepository extends FinanceRepository implements JournalEntryLineRepositoryInterface
{
    public function __construct(JournalEntryLineModel $model)
    {
        parent::__construct($model);
    }
}
