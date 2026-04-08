<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Tenant\Application\Contracts\OrgUnitServiceInterface;
use Modules\Tenant\Application\DTOs\OrgUnitData;
use Modules\Tenant\Infrastructure\Http\Requests\StoreOrgUnitRequest;
use Modules\Tenant\Infrastructure\Http\Requests\UpdateOrgUnitRequest;
use Modules\Tenant\Infrastructure\Http\Resources\OrgUnitResource;

/**
 * @OA\Tag(name="OrgUnits", description="Organisational unit management endpoints")
 */
final class OrgUnitController extends AuthorizedController
{
    public function __construct(private readonly OrgUnitServiceInterface $service) {}

    /**
     * @OA\Get(
     *     path="/api/org-units",
     *     tags={"OrgUnits"},
     *     summary="List org units",
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Paginated list")
     * )
     */
    public function index(): ResourceCollection
    {
        $paginated = $this->service->list();

        return OrgUnitResource::collection($paginated);
    }

    /**
     * @OA\Post(
     *     path="/api/org-units",
     *     tags={"OrgUnits"},
     *     summary="Create an org unit",
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/StoreOrgUnitRequest")),
     *     @OA\Response(response=201, description="Created")
     * )
     */
    public function store(StoreOrgUnitRequest $request): JsonResponse
    {
        $dto = OrgUnitData::fromArray($request->validated());
        $orgUnit = $this->service->create($dto);

        return (new OrgUnitResource($orgUnit))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @OA\Get(
     *     path="/api/org-units/{id}",
     *     tags={"OrgUnits"},
     *     summary="Get an org unit",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="OrgUnit details"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(int $org_unit): JsonResponse
    {
        $record = $this->service->find($org_unit);

        return (new OrgUnitResource($record))->response();
    }

    /**
     * @OA\Put(
     *     path="/api/org-units/{id}",
     *     tags={"OrgUnits"},
     *     summary="Update an org unit",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/UpdateOrgUnitRequest")),
     *     @OA\Response(response=200, description="Updated")
     * )
     */
    public function update(UpdateOrgUnitRequest $request, int $org_unit): JsonResponse
    {
        $record = $this->service->update($org_unit, $request->validated());

        return (new OrgUnitResource($record))->response();
    }

    /**
     * @OA\Delete(
     *     path="/api/org-units/{id}",
     *     tags={"OrgUnits"},
     *     summary="Soft-delete an org unit",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Deleted")
     * )
     */
    public function destroy(int $org_unit): JsonResponse
    {
        $this->service->delete($org_unit);

        return response()->json(null, 204);
    }

    /**
     * @OA\Patch(
     *     path="/api/org-units/{id}/move",
     *     tags={"OrgUnits"},
     *     summary="Move an org unit to a new parent",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="parent_id", type="integer", nullable=true)
     *     )),
     *     @OA\Response(response=200, description="Moved")
     * )
     */
    public function move(Request $request, int $org_unit): JsonResponse
    {
        $request->validate([
            'parent_id' => ['nullable', 'integer', 'min:1', 'exists:org_units,id'],
        ]);

        $record = $this->service->move($org_unit, $request->input('parent_id'));

        return (new OrgUnitResource($record))->response();
    }
}
