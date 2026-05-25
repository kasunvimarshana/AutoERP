<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorRunningCharts\CreateVehicleRentalLessorRunningChartServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorRunningCharts\DeleteVehicleRentalLessorRunningChartServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorRunningCharts\GetVehicleRentalLessorRunningChartServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorRunningCharts\ListVehicleRentalLessorRunningChartsServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorRunningCharts\UpdateVehicleRentalLessorRunningChartServiceInterface;
use Modules\VehicleRental\Presentation\Http\Requests\ListVehicleRentalLessorRunningChartRequest;
use Modules\VehicleRental\Presentation\Http\Requests\UpsertVehicleRentalLessorRunningChartRequest;
use Modules\VehicleRental\Presentation\Http\Resources\VehicleRentalLessorRunningChartResource;

final class VehicleRentalLessorRunningChartController extends Controller
{
    public function __construct(
        private readonly ListVehicleRentalLessorRunningChartsServiceInterface $listService,
        private readonly GetVehicleRentalLessorRunningChartServiceInterface $getService,
        private readonly CreateVehicleRentalLessorRunningChartServiceInterface $createService,
        private readonly UpdateVehicleRentalLessorRunningChartServiceInterface $updateService,
        private readonly DeleteVehicleRentalLessorRunningChartServiceInterface $deleteService,
    ) {
    }

    public function index(ListVehicleRentalLessorRunningChartRequest $request): JsonResponse
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
            'data' => VehicleRentalLessorRunningChartResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|VehicleRentalLessorRunningChartResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new VehicleRentalLessorRunningChartResource($result->valueOrFail());
    }

    public function store(UpsertVehicleRentalLessorRunningChartRequest $request): JsonResponse|VehicleRentalLessorRunningChartResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new VehicleRentalLessorRunningChartResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertVehicleRentalLessorRunningChartRequest $request, int|string $id): JsonResponse|VehicleRentalLessorRunningChartResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'VEHICLERENTAL_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new VehicleRentalLessorRunningChartResource($result->valueOrFail());
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