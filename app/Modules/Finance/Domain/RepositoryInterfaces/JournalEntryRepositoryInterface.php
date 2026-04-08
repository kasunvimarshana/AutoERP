<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface JournalEntryRepositoryInterface extends RepositoryInterface
{
    /**
     * Find a journal entry with all its lines eagerly loaded.
     */
    public function findWithLines(int $id): mixed;

    /**
     * Find a journal entry by its reference number within a tenant.
     */
    public function findByReference(string $referenceNumber, int $tenantId): mixed;

    /**
     * Retrieve journal entries for a tenant within a date range.
     */
    public function findByDateRange(int $tenantId, string $fromDate, string $toDate): Collection;

    /**
     * Retrieve posted journal entries for a tenant within a date range.
     */
    public function findPostedByDateRange(int $tenantId, string $fromDate, string $toDate): Collection;

    /**
     * Generate the next reference number for the tenant.
     */
    public function nextReferenceNumber(int $tenantId): string;
}
