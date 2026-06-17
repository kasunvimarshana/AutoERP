<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Models\VehicleDocument;
use Modules\Vehicle\Http\Requests\ListVehicleRequest;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Vehicle\Http\Requests\StoreVehicleAttributeRequest;
use Modules\Vehicle\Http\Requests\StoreVehicleDocumentRequest;
use Modules\Vehicle\Http\Requests\StoreVehicleOwnershipRequest;
use Modules\Vehicle\Http\Requests\UpdateVehicleAttributeRequest;
use Modules\Vehicle\Http\Requests\UpdateVehicleDocumentRequest;
use Modules\Vehicle\Http\Requests\UpdateVehicleOwnershipRequest;
use Modules\Vehicle\Http\Resources\VehicleAttributeResource;
use Modules\Vehicle\Http\Resources\VehicleDocumentResource;
use Modules\Vehicle\Http\Resources\VehicleOwnershipResource;
use Modules\Vehicle\Http\Resources\VehicleStatusHistoryResource;
use Modules\Vehicle\Services\VehicleAttributeService;
use Modules\Vehicle\Services\VehicleAuthorizationService;
use Modules\Vehicle\Services\VehicleDocumentService;
use Modules\Vehicle\Services\VehicleOwnershipService;
use Modules\Vehicle\Services\VehicleQueryService;
use Modules\Vehicle\Services\VehicleRelationQueryService;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

final class VehicleRelationController
{
    public function __construct(
        private readonly VehicleQueryService $vehicles,
        private readonly VehicleRelationQueryService $relations,
        private readonly VehicleDocumentService $documents,
        private readonly VehicleOwnershipService $ownerships,
        private readonly VehicleAttributeService $attributes,
        private readonly VehicleAuthorizationService $authorization,
    ) {}

    public function documents(ListVehicleRequest $request, int $vehicle): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleAuthorizationService::VIEW);

        return VehicleDocumentResource::collection($this->relations->documents($this->vehicle($request, $vehicle), $request->perPage()));
    }

    public function storeDocument(StoreVehicleDocumentRequest $request, int $vehicle): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleAuthorizationService::MANAGE_DOCUMENTS);

        return (new VehicleDocumentResource($this->documents->create($this->vehicle($request, $vehicle), $request->toData())))->response()->setStatusCode(201);
    }

    public function updateDocument(UpdateVehicleDocumentRequest $request, int $vehicle, int $document): VehicleDocumentResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleAuthorizationService::MANAGE_DOCUMENTS);

        $model = $this->vehicle($request, $vehicle);
        return new VehicleDocumentResource($this->documents->update($model, $this->relations->document($model, $document), $request->toData()));
    }

    public function destroyDocument(ListVehicleRequest $request, int $vehicle, int $document): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleAuthorizationService::MANAGE_DOCUMENTS);

        $model = $this->vehicle($request, $vehicle);
        $this->documents->delete($model, $this->relations->document($model, $document));
        return response()->json(null, 204);
    }

    public function previewDocument(ListVehicleRequest $request, int $vehicle, int $document): Response
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleAuthorizationService::DOWNLOAD_DOCUMENTS);

        $model = $this->vehicle($request, $vehicle);

        return $this->documentResponse($model, $this->relations->document($model, $document), true);
    }

    public function downloadDocument(ListVehicleRequest $request, int $vehicle, int $document): Response
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleAuthorizationService::DOWNLOAD_DOCUMENTS);

        $model = $this->vehicle($request, $vehicle);

        return $this->documentResponse($model, $this->relations->document($model, $document), false);
    }

    public function ownerships(ListVehicleRequest $request, int $vehicle): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleAuthorizationService::VIEW);

        return VehicleOwnershipResource::collection($this->relations->ownerships($this->vehicle($request, $vehicle), $request->perPage()));
    }

    public function storeOwnership(StoreVehicleOwnershipRequest $request, int $vehicle): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleAuthorizationService::UPDATE);

        return (new VehicleOwnershipResource($this->ownerships->assign($this->vehicle($request, $vehicle), $request->toData())))->response()->setStatusCode(201);
    }

    public function updateOwnership(UpdateVehicleOwnershipRequest $request, int $vehicle, int $ownership): VehicleOwnershipResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleAuthorizationService::UPDATE);

        $model = $this->vehicle($request, $vehicle);
        return new VehicleOwnershipResource($this->ownerships->update($model, $this->relations->ownership($model, $ownership), $request->toData()));
    }

    public function destroyOwnership(ListVehicleRequest $request, int $vehicle, int $ownership): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleAuthorizationService::UPDATE);

        $model = $this->vehicle($request, $vehicle);
        $this->ownerships->delete($model, $this->relations->ownership($model, $ownership));
        return response()->json(null, 204);
    }

    public function attributes(ListVehicleRequest $request, int $vehicle): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleAuthorizationService::VIEW);

        return VehicleAttributeResource::collection($this->relations->attributes($this->vehicle($request, $vehicle), $request->perPage()));
    }

    public function storeAttribute(StoreVehicleAttributeRequest $request, int $vehicle): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleAuthorizationService::MANAGE_ATTRIBUTES);

        return (new VehicleAttributeResource($this->attributes->create($this->vehicle($request, $vehicle), $request->toData())))->response()->setStatusCode(201);
    }

    public function updateAttribute(UpdateVehicleAttributeRequest $request, int $vehicle, int $attribute): VehicleAttributeResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleAuthorizationService::MANAGE_ATTRIBUTES);

        $model = $this->vehicle($request, $vehicle);
        return new VehicleAttributeResource($this->attributes->update($model, $this->relations->attribute($model, $attribute), $request->toData()));
    }

    public function destroyAttribute(ListVehicleRequest $request, int $vehicle, int $attribute): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleAuthorizationService::MANAGE_ATTRIBUTES);

        $model = $this->vehicle($request, $vehicle);
        $this->attributes->delete($model, $this->relations->attribute($model, $attribute));
        return response()->json(null, 204);
    }

    public function statusHistory(ListVehicleRequest $request, int $vehicle): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleAuthorizationService::VIEW);

        return VehicleStatusHistoryResource::collection($this->relations->statusHistory($this->vehicle($request, $vehicle), $request->perPage()));
    }

    private function documentResponse(Vehicle $vehicle, VehicleDocument $document, bool $inline): Response
    {
        $content = $this->documents->content($vehicle, $document);
        if ($content === null) {
            abort(404, 'Vehicle document file not found.');
        }

        return response($content, 200, [
            'Content-Type' => $this->documents->mimeType($vehicle, $document),
            'Content-Disposition' => HeaderUtils::makeDisposition(
                $inline ? ResponseHeaderBag::DISPOSITION_INLINE : ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $this->documents->downloadName($document),
            ),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function vehicle(TenantScopedRequest $request, int $vehicle): Vehicle
    {
        return $this->vehicles->vehicle($vehicle, $request->tenantId(), $request->organizationUnitId());
    }
}
