<?php

declare(strict_types=1);

namespace Modules\VehicleService\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceDiagnostics\CreateVehicleServiceDiagnosticServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceDiagnostics\DeleteVehicleServiceDiagnosticServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceDiagnostics\GetVehicleServiceDiagnosticServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceDiagnostics\ListVehicleServiceDiagnosticsServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceDiagnostics\UpdateVehicleServiceDiagnosticServiceInterface;
use Modules\VehicleService\Presentation\Http\Requests\ListVehicleServiceDiagnosticRequest;
use Modules\VehicleService\Presentation\Http\Requests\UpsertVehicleServiceDiagnosticRequest;
use Modules\VehicleService\Presentation\Http\Resources\VehicleServiceDiagnosticResource;

final class VehicleServiceDiagnosticController extends Controller
{
    public function __construct(
        private readonly ListVehicleServiceDiagnosticsServiceInterface $listService,
        private readonly GetVehicleServiceDiagnosticServiceInterface $getService,
        private readonly CreateVehicleServiceDiagnosticServiceInterface $createService,
        private readonly UpdateVehicleServiceDiagnosticServiceInterface $updateService,
        private readonly DeleteVehicleServiceDiagnosticServiceInterface $deleteService,
    ) {
    }

    public function index(ListVehicleServiceDiagnosticRequest $request): JsonResponse
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
            'data' => VehicleServiceDiagnosticResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|VehicleServiceDiagnosticResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new VehicleServiceDiagnosticResource($result->valueOrFail());
    }

    public function store(UpsertVehicleServiceDiagnosticRequest $request): JsonResponse|VehicleServiceDiagnosticResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new VehicleServiceDiagnosticResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertVehicleServiceDiagnosticRequest $request, int|string $id): JsonResponse|VehicleServiceDiagnosticResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'VEHICLESERVICE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new VehicleServiceDiagnosticResource($result->valueOrFail());
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