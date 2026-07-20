<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\VehicleRental\Http\Requests\ListRentalRequest;
use Modules\VehicleRental\Http\Requests\RentalActionRequest;
use Modules\VehicleRental\Http\Requests\ReverseRentalRunningChartRequest;
use Modules\VehicleRental\Http\Requests\StoreRentalRunningChartRequest;
use Modules\VehicleRental\Http\Requests\UpdateRentalRunningChartRequest;
use Modules\VehicleRental\Http\Resources\RentalRunningChartResource;
use Modules\VehicleRental\Models\RentalRunningChart;
use Modules\VehicleRental\Services\RentalRunningChartService;

final class RentalRunningChartController extends RentalController
{
    public function index(ListRentalRequest $request, RentalRunningChartService $service): AnonymousResourceCollection
    {
        $query = RentalRunningChart::query()
            ->forContext($request->tenantId(), $request->organizationUnitId())
            ->with($service->relations())
            ->orderByDesc('operational_date')
            ->orderByDesc('id');
        if ($request->filled('assignment_id')) {
            $query->where('assignment_id', $request->validated('assignment_id'));
        }
        if ($request->filled('agreement_id')) {
            $query->whereHas('assignment', fn (Builder $assignment): Builder => $assignment->where('agreement_id', $request->validated('agreement_id')));
        }
        if ($request->filled('vehicle_id')) {
            $query->whereHas('assignment', fn (Builder $assignment): Builder => $assignment->where('vehicle_id', $request->validated('vehicle_id')));
        }
        if ($request->filled('running_chart_status')) {
            $query->where('status', $request->validated('running_chart_status'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('operational_date', '>=', $request->validated('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('operational_date', '<=', $request->validated('date_to'));
        }

        return RentalRunningChartResource::collection($query->paginate($request->perPage()));
    }

    public function store(StoreRentalRunningChartRequest $request, RentalRunningChartService $service): JsonResponse
    {
        return (new RentalRunningChartResource($service->create($request->toData())))
            ->response()->setStatusCode(201);
    }

    public function show(ListRentalRequest $request, int $runningChart, RentalRunningChartService $service): RentalRunningChartResource
    {
        return new RentalRunningChartResource($this->runningChart($request, $runningChart)->load($service->relations()));
    }

    public function update(UpdateRentalRunningChartRequest $request, int $runningChart, RentalRunningChartService $service): RentalRunningChartResource
    {
        $chart = $this->runningChart($request, $runningChart);

        return new RentalRunningChartResource($service->update(
            $chart,
            $request->toData((int) $chart->assignment_id),
            $request->expectedVersion(),
        ));
    }

    public function finalize(RentalActionRequest $request, int $runningChart, RentalRunningChartService $service): RentalRunningChartResource
    {
        return new RentalRunningChartResource($service->finalize(
            $this->runningChart($request, $runningChart),
            $request->expectedVersion(),
            $request->currentUserId(),
        ));
    }

    public function reverse(ReverseRentalRunningChartRequest $request, int $runningChart, RentalRunningChartService $service): RentalRunningChartResource
    {
        return new RentalRunningChartResource($service->reverse(
            $this->runningChart($request, $runningChart),
            $request->expectedVersion(),
            $request->reason(),
            $request->currentUserId(),
        ));
    }
}
