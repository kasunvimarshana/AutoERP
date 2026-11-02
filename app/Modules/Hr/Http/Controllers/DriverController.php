<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Hr\Http\Requests\ChangeDriverStatusRequest;
use Modules\Hr\Http\Requests\ListDriverRequest;
use Modules\Hr\Http\Requests\StoreDriverRequest;
use Modules\Hr\Http\Requests\UpdateDriverRequest;
use Modules\Hr\Http\Resources\DriverResource;
use Modules\Hr\Http\Resources\DriverSummaryResource;
use Modules\Hr\Services\HrDriverService;

final class DriverController
{
    public function __construct(
        private readonly HrDriverService $drivers,
    ) {}

    public function index(ListDriverRequest $request): AnonymousResourceCollection
    {
        $paginator = $this->drivers->paginate(
            $request->validated(),
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->perPage(),
        );

        return DriverSummaryResource::collection($paginator);
    }

    public function store(StoreDriverRequest $request): JsonResponse
    {
        $driver = $this->drivers->create(
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->validated(),
            $request->currentUserId(),
        );

        return (new DriverResource($driver))
            ->response()
            ->setStatusCode(201);
    }

    public function show(ListDriverRequest $request, int $driver): DriverResource
    {
        $model = $this->drivers->find(
            $driver,
            $request->tenantId(),
            $request->organizationUnitId(),
        );

        return new DriverResource($model);
    }

    public function update(UpdateDriverRequest $request, int $driver): DriverResource
    {
        $model = $this->drivers->find(
            $driver,
            $request->tenantId(),
            $request->organizationUnitId(),
        );

        $updated = $this->drivers->update(
            $model,
            $request->validated(),
            $request->currentUserId(),
        );

        return new DriverResource($updated);
    }

    public function destroy(ListDriverRequest $request, int $driver): JsonResponse
    {
        $model = $this->drivers->find(
            $driver,
            $request->tenantId(),
            $request->organizationUnitId(),
        );

        $this->drivers->delete($model, $request->tenantId());

        return response()->json([
            'message' => 'Driver record successfully deleted.',
        ], 200);
    }

    public function changeStatus(ChangeDriverStatusRequest $request, int $driver): DriverResource
    {
        $model = $this->drivers->find(
            $driver,
            $request->tenantId(),
            $request->organizationUnitId(),
        );

        $validated = $request->validated();
        $updated = $this->drivers->changeStatus(
            $model,
            (string) $validated['status'],
            isset($validated['availability_status']) ? (string) $validated['availability_status'] : null,
            isset($validated['reason']) ? (string) $validated['reason'] : null,
            $request->currentUserId(),
        );

        return new DriverResource($updated);
    }

    public function lookup(ListDriverRequest $request): JsonResponse
    {
        $results = $this->drivers->lookup(
            $request->validated(),
            $request->tenantId(),
            $request->organizationUnitId(),
            min(100, $request->perPage()),
        );

        return response()->json([
            'data' => $results,
        ]);
    }
}
