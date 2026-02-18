<?php

declare(strict_types=1);

namespace Modules\Accounting\Repositories;

use Modules\Accounting\Models\JournalEntry;
use Modules\Core\Repositories\BaseRepository;

/**
 * Journal Entry Repository
 */
class JournalEntryRepository extends BaseRepository
{
    protected function model(): string
    {
        return JournalEntry::class;
    }

    public function findByEntryNumber(string $entryNumber): ?JournalEntry
    {
        return $this->newQuery()->where('entry_number', $entryNumber)->first();
    }

    public function getPostedEntries(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->newQuery()->where('is_posted', true)->orderBy('entry_date', 'desc')->get();
    }

    public function getUnpostedEntries(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->newQuery()->where('is_posted', false)->orderBy('entry_date', 'desc')->get();
    }
}
