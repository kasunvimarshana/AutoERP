<?php

declare(strict_types=1);

namespace Modules\Financial\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Financial\Domain\Contracts\Repositories\JournalEntryRepositoryInterface;
use Modules\Financial\Infrastructure\Persistence\Eloquent\Models\JournalEntryModel;

class EloquentJournalEntryRepository extends EloquentRepository implements JournalEntryRepositoryInterface
{
    public function __construct(JournalEntryModel $model)
    {
        parent::__construct($model);
    }

    /**
     * Find a journal entry by its unique entry number.
     */
    public function findByEntryNumber(string $number): mixed
    {
        return $this->model->newQuery()->where('entry_number', $number)->first();
    }

    /**
     * Get all journal entries within a date range.
     */
    public function findByDateRange(string $from, string $to): Collection
    {
        return $this->model->newQuery()
            ->whereBetween('entry_date', [$from, $to])
            ->get();
    }
}
