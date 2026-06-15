<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Vehicle\Http\Requests\ListVehicleRequest;
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
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Services\VehicleAttributeService;
use Modules\Vehicle\Services\VehicleDocumentService;
use Modules\Vehicle\Services\VehicleOwnershipService;
use Modules\Vehicle\Services\VehicleQueryService;
use Modules\Vehicle\Services\VehicleRelationQueryService;

final class VehicleRelationController
{
    public function __construct(
        private readonly VehicleQueryService $vehicles,
        private readonly VehicleRelationQueryService $relations,
        private readonly VehicleDocumentService $documents,
        private readonly VehicleOwnershipService $ownerships,
        private readonly VehicleAttributeService $attributes,
    ) {}

    public function documents(ListVehicleRequest $request, int $vehicle): AnonymousResourceCollection
    {
        return VehicleDocumentResource::collection($this->relations->documents($this->vehicle($request, $vehicle), $request->perPage()));
    }

    public function storeDocument(StoreVehicleDocumentRequest $request, int $vehicle): JsonResponse
    {
        return (new VehicleDocumentResource($this->documents->create($this->vehicle($request, $vehicle), $request->toData())))->response()->setStatusCode(201);
    }

    public function updateDocument(UpdateVehicleDocumentRequest $request, int $vehicle, int $document): VehicleDocumentResource
    {
        $model = $this->vehicle($request, $vehicle);

        return new VehicleDocumentResource($this->documents->update($model, $this->relations->document($model, $document), $request->toData()));
    }

    public function destroyDocument(ListVehicleRequest $request, int $vehicle, int $document): JsonResponse
    {
        $model = $this->vehicle($request, $vehicle);
        $this->documents->delete($model, $this->relations->document($model, $document));

        return response()->json(null, 204);
    }

    public function ownerships(ListVehicleRequest $request, int $vehicle): AnonymousResourceCollection
    {
        return VehicleOwnershipResource::collection($this->relations->ownerships($this->vehicle($request, $vehicle), $request->perPage()));
    }

    public function storeOwnership(StoreVehicleOwnershipRequest $request, int $vehicle): JsonResponse
    {
        return (new VehicleOwnershipResource($this->ownerships->assign($this->vehicle($request, $vehicle), $request->toData())))->response()->setStatusCode(201);
    }

    public function updateOwnership(UpdateVehicleOwnershipRequest $request, int $vehicle, int $ownership): JsonResponse
    {
        $model = $this->vehicle($request, $vehicle);

        return (new VehicleOwnershipResource(
            $this->ownerships->update($model, $this->relations->ownership($model, $ownership), $request->toData()),
        ))->response()->setStatusCode(200);
    }

    public function destroyOwnership(ListVehicleRequest $request, int $vehicle, int $ownership): JsonResponse
    {
        $model = $this->vehicle($request, $vehicle);
        $this->ownerships->delete($model, $this->relations->ownership($model, $ownership));

        return response()->json(null, 204);
    }

    public function attributes(ListVehicleRequest $request, int $vehicle): AnonymousResourceCollection
    {
        return VehicleAttributeResource::collection($this->relations->attributes($this->vehicle($request, $vehicle), $request->perPage()));
    }

    public function storeAttribute(StoreVehicleAttributeRequest $request, int $vehicle): JsonResponse
    {
        return (new VehicleAttributeResource($this->attributes->create($this->vehicle($request, $vehicle), $request->toData())))->response()->setStatusCode(201);
    }

    public function updateAttribute(UpdateVehicleAttributeRequest $request, int $vehicle, int $attribute): VehicleAttributeResource
    {
        $model = $this->vehicle($request, $vehicle);

        return new VehicleAttributeResource($this->attributes->update($model, $this->relations->attribute($model, $attribute), $request->toData()));
    }

    public function destroyAttribute(ListVehicleRequest $request, int $vehicle, int $attribute): JsonResponse
    {
        $model = $this->vehicle($request, $vehicle);
        $this->attributes->delete($model, $this->relations->attribute($model, $attribute));

        return response()->json(null, 204);
    }

    public function statusHistory(ListVehicleRequest $request, int $vehicle): AnonymousResourceCollection
    {
        return VehicleStatusHistoryResource::collection($this->relations->statusHistory($this->vehicle($request, $vehicle), $request->perPage()));
    }

    private function vehicle(TenantScopedRequest $request, int $vehicle): Vehicle
    {
        return $this->vehicles->vehicle($vehicle, $request->tenantId(), $request->organizationUnitId());
    }
}
