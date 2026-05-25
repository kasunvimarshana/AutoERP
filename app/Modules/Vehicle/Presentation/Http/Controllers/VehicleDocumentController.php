<?php

declare(strict_types=1);

namespace Modules\Vehicle\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Vehicle\Application\Contracts\UseCases\VehicleDocuments\CreateVehicleDocumentServiceInterface;
use Modules\Vehicle\Application\Contracts\UseCases\VehicleDocuments\DeleteVehicleDocumentServiceInterface;
use Modules\Vehicle\Application\Contracts\UseCases\VehicleDocuments\GetVehicleDocumentServiceInterface;
use Modules\Vehicle\Application\Contracts\UseCases\VehicleDocuments\ListVehicleDocumentsServiceInterface;
use Modules\Vehicle\Application\Contracts\UseCases\VehicleDocuments\UpdateVehicleDocumentServiceInterface;
use Modules\Vehicle\Presentation\Http\Requests\ListVehicleDocumentRequest;
use Modules\Vehicle\Presentation\Http\Requests\UpsertVehicleDocumentRequest;
use Modules\Vehicle\Presentation\Http\Resources\VehicleDocumentResource;

final class VehicleDocumentController extends Controller
{
    public function __construct(
        private readonly ListVehicleDocumentsServiceInterface $listDocuments,
        private readonly GetVehicleDocumentServiceInterface $getDocument,
        private readonly CreateVehicleDocumentServiceInterface $createDocument,
        private readonly UpdateVehicleDocumentServiceInterface $updateDocument,
        private readonly DeleteVehicleDocumentServiceInterface $deleteDocument,
    ) {
    }

    public function index(ListVehicleDocumentRequest $request): JsonResponse
    {
        $result = $this->listDocuments->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        $page = $result->valueOrFail();
        if (! $page instanceof PagedResult) {
            return response()->json(['message' => 'Unexpected list response.'], 500);
        }

        return response()->json([
            'data' => VehicleDocumentResource::collection($page->items)->resolve(),
            'meta' => [
                'total' => $page->total,
                'page' => $page->page,
                'per_page' => $page->perPage,
                'page_count' => $page->pageCount(),
                'has_more' => $page->hasMore(),
            ],
        ]);
    }

    public function show(int|string $vehicleDocument): JsonResponse|VehicleDocumentResource
    {
        $result = $this->getDocument->execute($vehicleDocument);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new VehicleDocumentResource($result->valueOrFail());
    }

    public function store(UpsertVehicleDocumentRequest $request): JsonResponse|VehicleDocumentResource
    {
        $result = $this->createDocument->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new VehicleDocumentResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(
        UpsertVehicleDocumentRequest $request,
        int|string $vehicleDocument,
    ): JsonResponse|VehicleDocumentResource {
        $result = $this->updateDocument->execute($vehicleDocument, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'VEHICLE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new VehicleDocumentResource($result->valueOrFail());
    }

    public function destroy(int|string $vehicleDocument): JsonResponse
    {
        $result = $this->deleteDocument->execute($vehicleDocument);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }
}
