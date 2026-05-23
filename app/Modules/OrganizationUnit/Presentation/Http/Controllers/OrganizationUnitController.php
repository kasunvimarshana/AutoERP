<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\OrganizationUnit\Application\DTOs\OrganizationUnitData;
use Modules\OrganizationUnit\Application\Services\OrganizationUnitService;
use Modules\OrganizationUnit\Domain\Exceptions\OrganizationUnitRecordNotFoundException;
use Modules\OrganizationUnit\Presentation\Http\Controllers\Concerns\HandlesOrganizationUnitHttp;
use Modules\OrganizationUnit\Presentation\Http\Requests\StoreOrganizationUnitRequest;
use Modules\OrganizationUnit\Presentation\Http\Requests\UpdateOrganizationUnitRequest;
use Modules\OrganizationUnit\Presentation\Http\Resources\OrganizationUnitResource;

class OrganizationUnitController extends Controller
{
    use HandlesOrganizationUnitHttp;

    public function __construct(private readonly OrganizationUnitService $organizationUnits) {}

    public function index(Request $request, int|string $tenant): mixed
    {
        try {
            return OrganizationUnitResource::collection($this->organizationUnits->listUnits($tenant, $this->perPage($request)));
        } catch (OrganizationUnitRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function store(StoreOrganizationUnitRequest $request, int|string $tenant): JsonResponse
    {
        try {
            $unit = $this->organizationUnits->createUnit(OrganizationUnitData::fromArray($tenant, $request->validated()));

            return (new OrganizationUnitResource($unit))->response()->setStatusCode(201);
        } catch (OrganizationUnitRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function show(int|string $tenant, int|string $unit): OrganizationUnitResource|JsonResponse
    {
        try {
            return new OrganizationUnitResource($this->organizationUnits->findUnit($tenant, $unit));
        } catch (OrganizationUnitRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(UpdateOrganizationUnitRequest $request, int|string $tenant, int|string $unit): OrganizationUnitResource|JsonResponse
    {
        try {
            return new OrganizationUnitResource($this->organizationUnits->updateUnit($tenant, $unit, OrganizationUnitData::fromArray($tenant, $request->validated())));
        } catch (OrganizationUnitRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $tenant, int|string $unit): JsonResponse
    {
        try {
            $this->organizationUnits->deleteUnit($tenant, $unit);

            return response()->json(null, 204);
        } catch (OrganizationUnitRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }
}
