<?php

declare(strict_types=1);

namespace Modules\VehicleService\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceDiagnosticLines\CreateVehicleServiceDiagnosticLineServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceDiagnosticLines\DeleteVehicleServiceDiagnosticLineServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceDiagnosticLines\GetVehicleServiceDiagnosticLineServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceDiagnosticLines\ListVehicleServiceDiagnosticLinesServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceDiagnosticLines\UpdateVehicleServiceDiagnosticLineServiceInterface;
use Modules\VehicleService\Presentation\Http\Requests\ListVehicleServiceDiagnosticLineRequest;
use Modules\VehicleService\Presentation\Http\Requests\UpsertVehicleServiceDiagnosticLineRequest;
use Modules\VehicleService\Presentation\Http\Resources\VehicleServiceDiagnosticLineResource;

final class VehicleServiceDiagnosticLineController extends Controller
{
    public function __construct(
        private readonly ListVehicleServiceDiagnosticLinesServiceInterface $listService,
        private readonly GetVehicleServiceDiagnosticLineServiceInterface $getService,
        private readonly CreateVehicleServiceDiagnosticLineServiceInterface $createService,
        private readonly UpdateVehicleServiceDiagnosticLineServiceInterface $updateService,
        private readonly DeleteVehicleServiceDiagnosticLineServiceInterface $deleteService,
    ) {
    }

    public function index(ListVehicleServiceDiagnosticLineRequest $request): JsonResponse
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
            'data' => VehicleServiceDiagnosticLineResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|VehicleServiceDiagnosticLineResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new VehicleServiceDiagnosticLineResource($result->valueOrFail());
    }

    public function store(UpsertVehicleServiceDiagnosticLineRequest $request): JsonResponse|VehicleServiceDiagnosticLineResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new VehicleServiceDiagnosticLineResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertVehicleServiceDiagnosticLineRequest $request, int|string $id): JsonResponse|VehicleServiceDiagnosticLineResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'VEHICLESERVICE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new VehicleServiceDiagnosticLineResource($result->valueOrFail());
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