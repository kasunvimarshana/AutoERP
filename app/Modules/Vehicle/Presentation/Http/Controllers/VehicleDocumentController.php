<?php

declare(strict_types=1);

namespace Modules\Vehicle\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Vehicle\Application\DTOs\VehicleDocumentData;
use Modules\Vehicle\Application\Services\VehicleService;
use Modules\Vehicle\Domain\Exceptions\VehicleRecordNotFoundException;
use Modules\Vehicle\Presentation\Http\Controllers\Concerns\HandlesVehicleHttp;
use Modules\Vehicle\Presentation\Http\Requests\StoreVehicleDocumentRequest;
use Modules\Vehicle\Presentation\Http\Requests\UpdateVehicleDocumentRequest;
use Modules\Vehicle\Presentation\Http\Resources\VehicleDocumentResource;

class VehicleDocumentController extends Controller
{
    use HandlesVehicleHttp;

    public function __construct(private readonly VehicleService $vehicles)
    {
    }

    public function index(Request $request, int|string $tenant, int|string $vehicle): mixed
    {
        try {
            return VehicleDocumentResource::collection(
                $this->vehicles->listDocuments($tenant, $vehicle, $this->perPage($request)),
            );
        } catch (VehicleRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function store(StoreVehicleDocumentRequest $request, int|string $tenant, int|string $vehicle): JsonResponse
    {
        try {
            $document = $this->vehicles->createDocument(
                VehicleDocumentData::fromArray($tenant, $vehicle, $request->validated()),
            );

            return (new VehicleDocumentResource($document))->response()->setStatusCode(201);
        } catch (VehicleRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function show(
        int|string $tenant,
        int|string $vehicle,
        int|string $document,
    ): VehicleDocumentResource|JsonResponse {
        try {
            return new VehicleDocumentResource($this->vehicles->findDocument($tenant, $vehicle, $document));
        } catch (VehicleRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(
        UpdateVehicleDocumentRequest $request,
        int|string $tenant,
        int|string $vehicle,
        int|string $document,
    ): VehicleDocumentResource|JsonResponse {
        try {
            return new VehicleDocumentResource(
                $this->vehicles->updateDocument(
                    $tenant,
                    $vehicle,
                    $document,
                    VehicleDocumentData::fromArray($tenant, $vehicle, $request->validated()),
                ),
            );
        } catch (VehicleRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $tenant, int|string $vehicle, int|string $document): JsonResponse
    {
        try {
            $this->vehicles->deleteDocument($tenant, $vehicle, $document);

            return response()->json(null, 204);
        } catch (VehicleRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }
}
