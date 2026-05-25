<?php

declare(strict_types=1);

namespace Modules\Supplier\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Supplier\Application\Contracts\UseCases\SupplierVehicles\CreateSupplierVehicleServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierVehicles\DeleteSupplierVehicleServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierVehicles\GetSupplierVehicleServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierVehicles\ListSupplierVehiclesServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierVehicles\UpdateSupplierVehicleServiceInterface;
use Modules\Supplier\Presentation\Http\Requests\ListSupplierVehicleRequest;
use Modules\Supplier\Presentation\Http\Requests\UpsertSupplierVehicleRequest;
use Modules\Supplier\Presentation\Http\Resources\SupplierVehicleResource;

final class SupplierVehicleController extends Controller
{
    public function __construct(
        private readonly ListSupplierVehiclesServiceInterface $listService,
        private readonly GetSupplierVehicleServiceInterface $getService,
        private readonly CreateSupplierVehicleServiceInterface $createService,
        private readonly UpdateSupplierVehicleServiceInterface $updateService,
        private readonly DeleteSupplierVehicleServiceInterface $deleteService,
    ) {
    }

    public function index(ListSupplierVehicleRequest $request): JsonResponse
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
            'data' => SupplierVehicleResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|SupplierVehicleResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new SupplierVehicleResource($result->valueOrFail());
    }

    public function store(UpsertSupplierVehicleRequest $request): JsonResponse|SupplierVehicleResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new SupplierVehicleResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertSupplierVehicleRequest $request, int|string $id): JsonResponse|SupplierVehicleResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'SUPPLIER_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new SupplierVehicleResource($result->valueOrFail());
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