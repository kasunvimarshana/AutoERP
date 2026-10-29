<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Http\Requests\CustomerOutstandingReportRequest;
use Modules\User\Services\UserAccessResolver;

final class CustomerOutstandingLocationScope
{
    public function __construct(private readonly UserAccessResolver $access) {}

    public function apply(Builder $query, CustomerOutstandingReportRequest $request, string $column = 'balances.organization_unit_id'): void
    {
        $ids = $this->ids($request);
        if ($ids === []) {
            $query->whereNull($column);

            return;
        }

        $query->whereIn($column, $ids);
    }

    public function assertAccessible(CustomerOutstandingReportRequest $request): void
    {
        $this->ids($request);
    }

    /** @return array<int, array{id: int, name: string}> */
    public function options(CustomerOutstandingReportRequest $request): array
    {
        $ids = $this->ids($request);
        if ($ids === []) {
            return [];
        }

        return DB::table('organization_units')
            ->where('tenant_id', $request->tenantId())
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (object $row): array => ['id' => (int) $row->id, 'name' => (string) $row->name])
            ->all();
    }

    /** @return list<int> */
    private function ids(CustomerOutstandingReportRequest $request): array
    {
        $userId = $request->currentUserId();
        $accessible = $userId === null
            ? array_filter([$request->organizationUnitId()])
            : $this->access->accessibleOrganizationUnitIds($userId, $request->tenantId());
        $selected = $request->input('location_id');
        if ($selected !== null && $selected !== '') {
            $locationId = (int) $selected;
            if (! in_array($locationId, $accessible, true)) {
                throw new AuthorizationException('This location is not available to the current user.');
            }

            return [$locationId];
        }

        if ($userId !== null && $accessible === []) {
            throw new AuthorizationException('No accessible locations were found for this report.');
        }

        return array_values($accessible);
    }
}
