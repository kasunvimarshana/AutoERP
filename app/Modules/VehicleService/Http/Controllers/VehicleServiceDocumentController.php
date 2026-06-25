<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\VehicleService\Http\Requests\ListVehicleServiceJobRequest;
use Modules\VehicleService\Http\Requests\StoreVehicleServiceDocumentRequest;
use Modules\VehicleService\Http\Requests\VehicleServiceActionRequest;
use Modules\VehicleService\Http\Resources\VehicleServiceDocumentResource;
use Modules\VehicleService\Services\VehicleServiceDocumentService;

final class VehicleServiceDocumentController extends VehicleServiceController
{
    public function index(
        ListVehicleServiceJobRequest $request,
        int $job,
    ): AnonymousResourceCollection {
        return VehicleServiceDocumentResource::collection(
            $this->job($request, $job)->documents()->latest()->get(),
        );
    }

    public function store(
        StoreVehicleServiceDocumentRequest $request,
        int $job,
        VehicleServiceDocumentService $service,
    ): JsonResponse {
        $document = $service->create(
            $this->job($request, $job),
            (string) $request->input('document_type'),
            $request->file('file'),
            $request->filled('file_path') ? (string) $request->input('file_path') : null,
            $request->filled('description') ? (string) $request->input('description') : null,
            $request->currentUserId(),
        );

        return (new VehicleServiceDocumentResource($document))->response()->setStatusCode(201);
    }

    public function destroy(
        VehicleServiceActionRequest $request,
        int $job,
        int $document,
        VehicleServiceDocumentService $service,
    ): JsonResponse {
        $model = $this->job($request, $job)->documents()->findOrFail($document);
        $service->delete($model);

        return response()->json(status: 204);
    }
}
