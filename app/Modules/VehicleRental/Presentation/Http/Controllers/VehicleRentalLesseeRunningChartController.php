<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeRunningCharts\CreateVehicleRentalLesseeRunningChartServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeRunningCharts\DeleteVehicleRentalLesseeRunningChartServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeRunningCharts\GetVehicleRentalLesseeRunningChartServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeRunningCharts\ListVehicleRentalLesseeRunningChartsServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeRunningCharts\UpdateVehicleRentalLesseeRunningChartServiceInterface;
use Modules\VehicleRental\Presentation\Http\Requests\ListVehicleRentalLesseeRunningChartRequest;
use Modules\VehicleRental\Presentation\Http\Requests\UpsertVehicleRentalLesseeRunningChartRequest;
use Modules\VehicleRental\Presentation\Http\Resources\VehicleRentalLesseeRunningChartResource;

final class VehicleRentalLesseeRunningChartController extends Controller
{
    public function __construct(
        private readonly ListVehicleRentalLesseeRunningChartsServiceInterface $listService,
        private readonly GetVehicleRentalLesseeRunningChartServiceInterface $getService,
        private readonly CreateVehicleRentalLesseeRunningChartServiceInterface $createService,
        private readonly UpdateVehicleRentalLesseeRunningChartServiceInterface $updateService,
        private readonly DeleteVehicleRentalLesseeRunningChartServiceInterface $deleteService,
    ) {
    }

    public function index(ListVehicleRentalLesseeRunningChartRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 0);
        $page = (int) ($validated['page'] ?? 0);
        unset($validated['per_page'], $validated['page']);

        $result = $this->listService->execute($validated, $perPage, $page);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        $pageResult = $result->valueOrFail();
        if (! $pageResult instanceof PagedResult) {
            return response()->json(['message' => 'Unexpected list response.'], 500);
        }

        return response()->json([
            'data' => VehicleRentalLesseeRunningChartResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|VehicleRentalLesseeRunningChartResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new VehicleRentalLesseeRunningChartResource($result->valueOrFail());
    }

    public function store(UpsertVehicleRentalLesseeRunningChartRequest $request): JsonResponse|VehicleRentalLesseeRunningChartResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new VehicleRentalLesseeRunningChartResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertVehicleRentalLesseeRunningChartRequest $request, int|string $id): JsonResponse|VehicleRentalLesseeRunningChartResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'VEHICLERENTAL_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new VehicleRentalLesseeRunningChartResource($result->valueOrFail());
    }

    public function destroy(int|string $id): JsonResponse
    {
        $result = $this->deleteService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }
}
