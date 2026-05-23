<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\OrganizationUnit\Application\DTOs\OrganizationUnitTypeData;
use Modules\OrganizationUnit\Application\Services\OrganizationUnitService;
use Modules\OrganizationUnit\Domain\Exceptions\OrganizationUnitRecordNotFoundException;
use Modules\OrganizationUnit\Presentation\Http\Controllers\Concerns\HandlesOrganizationUnitHttp;
use Modules\OrganizationUnit\Presentation\Http\Requests\StoreOrganizationUnitTypeRequest;
use Modules\OrganizationUnit\Presentation\Http\Requests\UpdateOrganizationUnitTypeRequest;
use Modules\OrganizationUnit\Presentation\Http\Resources\OrganizationUnitTypeResource;

class OrganizationUnitTypeController extends Controller
{
    use HandlesOrganizationUnitHttp;

    public function __construct(private readonly OrganizationUnitService $organizationUnits) {}

    public function index(Request $request, int|string $tenant): mixed
    {
        try {
            return OrganizationUnitTypeResource::collection($this->organizationUnits->listTypes($tenant, $this->perPage($request)));
        } catch (OrganizationUnitRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function store(StoreOrganizationUnitTypeRequest $request, int|string $tenant): JsonResponse
    {
        try {
            $type = $this->organizationUnits->createType(OrganizationUnitTypeData::fromArray($tenant, $request->validated()));

            return (new OrganizationUnitTypeResource($type))->response()->setStatusCode(201);
        } catch (OrganizationUnitRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function show(int|string $tenant, int|string $type): OrganizationUnitTypeResource|JsonResponse
    {
        try {
            return new OrganizationUnitTypeResource($this->organizationUnits->findType($tenant, $type));
        } catch (OrganizationUnitRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(UpdateOrganizationUnitTypeRequest $request, int|string $tenant, int|string $type): OrganizationUnitTypeResource|JsonResponse
    {
        try {
            return new OrganizationUnitTypeResource($this->organizationUnits->updateType($tenant, $type, OrganizationUnitTypeData::fromArray($tenant, $request->validated())));
        } catch (OrganizationUnitRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $tenant, int|string $type): JsonResponse
    {
        try {
            $this->organizationUnits->deleteType($tenant, $type);

            return response()->json(null, 204);
        } catch (OrganizationUnitRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }
}
