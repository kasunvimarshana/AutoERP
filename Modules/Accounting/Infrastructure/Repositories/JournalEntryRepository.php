<?php

declare(strict_types=1);

namespace Modules\Accounting\Infrastructure\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Accounting\Domain\Contracts\JournalEntryRepositoryContract;
use Modules\Accounting\Domain\Entities\JournalEntry;
use Modules\Accounting\Domain\Entities\JournalEntryLine;
use Modules\Core\Infrastructure\Repositories\AbstractRepository;

/**
 * Journal entry repository implementation.
 *
 * Extends the tenant-aware AbstractRepository.
 * All queries are automatically scoped to the current tenant.
 */
class JournalEntryRepository extends AbstractRepository implements JournalEntryRepositoryContract
{
    public function __construct()
    {
        $this->modelClass = JournalEntry::class;
    }

    /**
     * {@inheritdoc}
     */
    public function findPostedLinesByPeriod(int $fiscalPeriodId): Collection
    {
        return JournalEntryLine::query()
            ->whereHas(
                'journalEntry',
                static fn ($q) => $q->where('fiscal_period_id', $fiscalPeriodId)
                                    ->where('status', JournalEntry::STATUS_POSTED)
            )
            ->with('account.accountType')
            ->get();
    }
}
