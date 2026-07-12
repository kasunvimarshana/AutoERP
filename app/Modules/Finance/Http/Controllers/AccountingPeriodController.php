<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Controllers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Finance\Http\Requests\AccountingPeriodActionRequest;
use Modules\Finance\Http\Requests\ListAccountingPeriodsRequest;
use Modules\Finance\Http\Requests\StoreAccountingPeriodRequest;
use Modules\Finance\Http\Resources\AccountingPeriodResource;
use Modules\Finance\Models\FinanceAccountingPeriod;
use Modules\Finance\Services\AccountingPeriodService;

final class AccountingPeriodController
{
    public function index(
        ListAccountingPeriodsRequest $request,
        AccountingPeriodService $service,
    ): AnonymousResourceCollection {
        $query = $service->scopeQuery($request->tenantId(), $request->organizationUnitId())
            ->with('events');
        if ($request->filled('status')) {
            $query->where('status', $request->validated('status'));
        }
        if ($request->filled('date')) {
            $date = (string) $request->validated('date');
            $query->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date);
        }

        return AccountingPeriodResource::collection(
            $query->orderByDesc('start_date')->paginate($request->perPage()),
        );
    }

    public function store(
        StoreAccountingPeriodRequest $request,
        AccountingPeriodService $service,
    ): AccountingPeriodResource {
        return new AccountingPeriodResource($service->create($request->toData()));
    }

    public function show(
        ListAccountingPeriodsRequest $request,
        int $period,
        AccountingPeriodService $service,
    ): AccountingPeriodResource {
        return new AccountingPeriodResource(
            $service->scopeQuery($request->tenantId(), $request->organizationUnitId())
                ->with('events')
                ->findOrFail($period),
        );
    }

    public function close(
        AccountingPeriodActionRequest $request,
        int $period,
        AccountingPeriodService $service,
    ): AccountingPeriodResource {
        return new AccountingPeriodResource($service->close(
            $this->find($request, $period, $service),
            $request->expectedVersion(),
            $request->reason(),
            $request->currentUserId(),
        ));
    }

    public function reopen(
        AccountingPeriodActionRequest $request,
        int $period,
        AccountingPeriodService $service,
    ): AccountingPeriodResource {
        return new AccountingPeriodResource($service->reopen(
            $this->find($request, $period, $service),
            $request->expectedVersion(),
            $request->reason(),
            $request->currentUserId(),
        ));
    }

    private function find(
        AccountingPeriodActionRequest $request,
        int $period,
        AccountingPeriodService $service,
    ): FinanceAccountingPeriod {
        return $service->scopeQuery($request->tenantId(), $request->organizationUnitId())
            ->findOrFail($period);
    }
}
