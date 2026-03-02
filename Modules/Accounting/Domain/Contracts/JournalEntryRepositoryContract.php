<?php

declare(strict_types=1);

namespace Modules\Accounting\Domain\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Domain\Contracts\RepositoryContract;

/**
 * Journal entry repository contract.
 */
interface JournalEntryRepositoryContract extends RepositoryContract
{
    /**
     * Return all posted journal entry lines for a given fiscal period.
     *
     * Each line is eagerly loaded with its account and account type so that
     * financial statement aggregation can be done without additional queries.
     */
    public function findPostedLinesByPeriod(int $fiscalPeriodId): Collection;
}
