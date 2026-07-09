<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\VehicleService\Enums\VehicleServiceOperationalStatus;
use Modules\VehicleService\Http\Requests\ListVehicleServiceJobRequest;
use Modules\VehicleService\Http\Requests\StoreVehicleServiceInspectionRequest;
use Modules\VehicleService\Http\Requests\StoreVehicleServiceJobRequest;
use Modules\VehicleService\Http\Requests\VehicleServiceActionRequest;
use Modules\VehicleService\Http\Resources\VehicleServiceInspectionResource;
use Modules\VehicleService\Http\Resources\VehicleServiceJobResource;
use Modules\VehicleService\Http\Resources\VehicleServiceStatusHistoryResource;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Services\VehicleServiceInspectionService;
use Modules\VehicleService\Services\VehicleServiceJobQueryService;
use Modules\VehicleService\Services\VehicleServiceJobService;
use Modules\VehicleService\Services\VehicleServiceStatusService;

final class VehicleServiceJobController extends VehicleServiceController
{
    public function index(
        ListVehicleServiceJobRequest $request,
        VehicleServiceJobQueryService $queries,
    ): AnonymousResourceCollection {
        return VehicleServiceJobResource::collection($queries->paginate(
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->validated(),
            $request->perPage(),
        ));
    }

    public function lookup(
        ListVehicleServiceJobRequest $request,
        VehicleServiceJobQueryService $queries,
    ): JsonResponse {
        $jobs = $queries->lookup(
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->input('search'),
            $request->perPage(),
        );

        return response()->json(['data' => $jobs->map(fn (VehicleServiceJob $job) => [
            'id' => (int) $job->getKey(),
            'code' => $job->job_number,
            'name' => $job->job_number.' - '.($job->vehicle?->registration_number ?? $job->customer?->display_name ?? 'Service job'),
        ])->all()]);
    }

    public function store(StoreVehicleServiceJobRequest $request, VehicleServiceJobService $service): JsonResponse
    {
        return (new VehicleServiceJobResource($service->create($request->toData())))
            ->response()->setStatusCode(201);
    }

    public function show(
        ListVehicleServiceJobRequest $request,
        int $job,
        VehicleServiceJobService $service,
    ): VehicleServiceJobResource {
        return new VehicleServiceJobResource($this->job($request, $job)->load($service->relations()));
    }

    public function update(
        StoreVehicleServiceJobRequest $request,
        int $job,
        VehicleServiceJobService $service,
    ): VehicleServiceJobResource {
        return new VehicleServiceJobResource($service->update(
            $this->job($request, $job),
            $request->toData(),
            $request->expectedVersion(),
        ));
    }

    public function destroy(
        VehicleServiceActionRequest $request,
        int $job,
        VehicleServiceJobService $service,
    ): JsonResponse {
        $service->delete($this->job($request, $job), $request->expectedVersion());

        return response()->json(status: 204);
    }

    public function inspect(
        StoreVehicleServiceInspectionRequest $request,
        int $job,
        VehicleServiceInspectionService $service,
    ): VehicleServiceInspectionResource {
        return new VehicleServiceInspectionResource(
            $service->save($this->job($request, $job), $request->toData(true), $request->expectedVersion()),
        );
    }

    public function start(
        VehicleServiceActionRequest $request,
        int $job,
        VehicleServiceStatusService $service,
    ): VehicleServiceJobResource {
        return $this->changeOperationalStatus($request, $job, VehicleServiceOperationalStatus::InProgress, $service);
    }

    public function complete(
        VehicleServiceActionRequest $request,
        int $job,
        VehicleServiceStatusService $service,
    ): VehicleServiceJobResource {
        return $this->changeOperationalStatus($request, $job, VehicleServiceOperationalStatus::Completed, $service);
    }

    public function cancel(
        VehicleServiceActionRequest $request,
        int $job,
        VehicleServiceStatusService $service,
    ): VehicleServiceJobResource {
        return $this->changeOperationalStatus($request, $job, VehicleServiceOperationalStatus::Cancelled, $service);
    }

    public function inspection(
        ListVehicleServiceJobRequest $request,
        int $job,
    ): JsonResponse|VehicleServiceInspectionResource {
        $inspection = $this->job($request, $job)->inspection()->with('inspector')->first();

        return $inspection === null
            ? response()->json(['data' => null])
            : new VehicleServiceInspectionResource($inspection);
    }

    public function updateInspection(
        StoreVehicleServiceInspectionRequest $request,
        int $job,
        VehicleServiceInspectionService $service,
    ): VehicleServiceInspectionResource {
        return new VehicleServiceInspectionResource(
            $service->save($this->job($request, $job), $request->toData(), $request->expectedVersion()),
        );
    }

    public function statusHistory(
        ListVehicleServiceJobRequest $request,
        int $job,
    ): AnonymousResourceCollection {
        return VehicleServiceStatusHistoryResource::collection(
            $this->job($request, $job)->statusHistories()->latest('changed_at')->latest('id')->get(),
        );
    }

    private function changeOperationalStatus(
        VehicleServiceActionRequest $request,
        int $job,
        VehicleServiceOperationalStatus $status,
        VehicleServiceStatusService $service,
    ): VehicleServiceJobResource {
        return new VehicleServiceJobResource($service->changeOperational(
            $this->job($request, $job),
            $status,
            $request->currentUserId(),
            $request->input('reason'),
            $request->expectedVersion(),
        ));
    }
}
