<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Finance\Application\Repositories\JournalEntryRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\JournalEntryModel;

final class EloquentJournalEntryRepository extends FinanceRepository implements JournalEntryRepositoryInterface
{
    public function __construct(JournalEntryModel $model)
    {
        parent::__construct($model);
    }
}
