<?php

declare(strict_types=1);

namespace Modules\Financial\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Financial\Domain\Contracts\Repositories\JournalEntryLineRepositoryInterface;
use Modules\Financial\Infrastructure\Persistence\Eloquent\Models\JournalEntryLineModel;

class EloquentJournalEntryLineRepository extends EloquentRepository implements JournalEntryLineRepositoryInterface
{
    public function __construct(JournalEntryLineModel $model)
    {
        parent::__construct($model);
    }
}
