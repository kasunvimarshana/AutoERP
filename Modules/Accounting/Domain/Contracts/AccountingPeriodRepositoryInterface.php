<?php

namespace Modules\Accounting\Domain\Contracts;

interface AccountingPeriodRepositoryInterface
{
    public function findById(string $id): ?object;

    /** Return the open or locked period that contains the given date, or null. */
    public function findByDate(string $tenantId, string $date): ?object;

    /** Return true if any period for the tenant overlaps [startDate, endDate] and is not draft. */
    public function hasOverlap(string $tenantId, string $startDate, string $endDate, ?string $excludeId = null): bool;

    public function paginate(string $tenantId, int $perPage = 15): object;

    public function create(array $data): object;

    public function update(string $id, array $data): object;
}
