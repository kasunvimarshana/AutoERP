<?php

namespace Modules\HR\Domain\Contracts;

use Modules\Shared\Domain\Contracts\RepositoryInterface;

interface PayrollRunRepositoryInterface extends RepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): object;
    public function findActiveRunForPeriod(string $tenantId, string $start, string $end): ?object;

    /**
     * Iterate over all draft payroll runs in chunks to allow bulk processing
     * without loading the entire table into memory (timeout prevention).
     */
    public function chunkDraftRuns(int $chunkSize, callable $callback, ?string $tenantId = null): void;
}
