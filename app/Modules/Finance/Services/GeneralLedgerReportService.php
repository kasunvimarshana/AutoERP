<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\FinanceLedgerEntry;

final class GeneralLedgerReportService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(
        int $tenantId,
        ?int $organizationUnitId,
        array $filters = [],
        int $perPage = 50,
    ): LengthAwarePaginator {
        $query = FinanceLedgerEntry::query()
            ->where('tenant_id', $tenantId)
            ->with(['account', 'journalEntry', 'journalLine.account', 'dimension']);

        $this->scopeOrganization($query, $organizationUnitId);

        if (isset($filters['account_id'])) {
            $query->where('account_id', (int) $filters['account_id']);
        }
        if (isset($filters['date_from'])) {
            $query->whereDate('entry_date', '>=', (string) $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->whereDate('entry_date', '<=', (string) $filters['date_to']);
        }

        foreach (['source_module', 'source_type', 'source_id'] as $filter) {
            if (isset($filters[$filter])) {
                $query->where($filter, $filters[$filter]);
            }
        }

        return $query
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    private function scopeOrganization(Builder $query, ?int $organizationUnitId): void
    {
        $organizationUnitId === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $organizationUnitId);
    }
}
