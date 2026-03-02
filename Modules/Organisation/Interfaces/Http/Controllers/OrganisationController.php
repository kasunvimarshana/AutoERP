<?php

declare(strict_types=1);

namespace Modules\Organisation\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Interfaces\Http\Resources\ApiResponse;
use Modules\Organisation\Application\DTOs\CreateBranchDTO;
use Modules\Organisation\Application\DTOs\CreateDepartmentDTO;
use Modules\Organisation\Application\DTOs\CreateLocationDTO;
use Modules\Organisation\Application\DTOs\CreateOrganisationDTO;
use Modules\Organisation\Application\Services\OrganisationService;

/**
 * Organisation controller.
 *
 * Input validation, authorization checks, and response formatting ONLY.
 * No business logic — all delegated to OrganisationService.
 *
 * @OA\Tag(name="Organisation", description="Organisation management endpoints")
 */
class OrganisationController extends Controller
{
    public function __construct(private readonly OrganisationService $service) {}

    /**
     * @OA\Get(
     *     path="/api/v1/organisations",
     *     tags={"Organisation"},
     *     summary="List organisations (paginated)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Paginated list of organisations"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $paginator = $this->service->list($perPage);

        return ApiResponse::paginated($paginator, 'Organisations retrieved.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/organisations",
     *     tags={"Organisation"},
     *     summary="Create a new organisation",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","code"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="is_active", type="boolean", default=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Organisation created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'code'        => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $dto = CreateOrganisationDTO::fromArray($validated);
        $organisation = $this->service->create($dto);

        return ApiResponse::created($organisation, 'Organisation created.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/organisations/{id}",
     *     tags={"Organisation"},
     *     summary="Get a single organisation",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Organisation data"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $organisation = $this->service->show($id);

        return ApiResponse::success($organisation, 'Organisation retrieved.');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/organisations/{id}",
     *     tags={"Organisation"},
     *     summary="Update an organisation",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Organisation updated"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name'        => ['sometimes', 'required', 'string', 'max:255'],
            'code'        => ['sometimes', 'required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $organisation = $this->service->update($id, $validated);

        return ApiResponse::success($organisation, 'Organisation updated.');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/organisations/{id}",
     *     tags={"Organisation"},
     *     summary="Delete an organisation",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Deleted"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);

        return ApiResponse::noContent();
    }

    // -------------------------------------------------------------------------
    // Branch endpoints
    // -------------------------------------------------------------------------

    /**
     * @OA\Get(
     *     path="/api/v1/organisations/{orgId}/branches",
     *     tags={"Organisation"},
     *     summary="List branches for an organisation",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="orgId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="List of branches"),
     *     @OA\Response(response=404, description="Organisation not found")
     * )
     */
    public function listBranches(int $orgId): JsonResponse
    {
        $branches = $this->service->listBranches($orgId);

        return ApiResponse::success($branches, 'Branches retrieved.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/organisations/{orgId}/branches",
     *     tags={"Organisation"},
     *     summary="Create a branch under an organisation",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="orgId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","code"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="address", type="string", nullable=true),
     *             @OA\Property(property="is_active", type="boolean", default=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Branch created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function createBranch(Request $request, int $orgId): JsonResponse
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'code'      => ['required', 'string', 'max:50'],
            'address'   => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $dto    = CreateBranchDTO::fromArray(array_merge($validated, ['organisation_id' => $orgId]));
        $branch = $this->service->createBranch($dto);

        return ApiResponse::created($branch, 'Branch created.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/branches/{id}",
     *     tags={"Organisation"},
     *     summary="Show a single branch",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Branch data"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function showBranch(int $id): JsonResponse
    {
        $branch = $this->service->showBranch($id);

        return ApiResponse::success($branch, 'Branch retrieved.');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/branches/{id}",
     *     tags={"Organisation"},
     *     summary="Update a branch",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="address", type="string", nullable=true),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Branch updated"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updateBranch(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name'      => ['sometimes', 'required', 'string', 'max:255'],
            'code'      => ['sometimes', 'required', 'string', 'max:50'],
            'address'   => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $branch = $this->service->updateBranch($id, $validated);

        return ApiResponse::success($branch, 'Branch updated.');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/branches/{id}",
     *     tags={"Organisation"},
     *     summary="Delete a branch",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Deleted"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function deleteBranch(int $id): JsonResponse
    {
        $this->service->deleteBranch($id);

        return ApiResponse::noContent();
    }

    // -------------------------------------------------------------------------
    // Location endpoints
    // -------------------------------------------------------------------------

    /**
     * @OA\Get(
     *     path="/api/v1/branches/{branchId}/locations",
     *     tags={"Organisation"},
     *     summary="List locations for a branch",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="branchId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="List of locations"),
     *     @OA\Response(response=404, description="Branch not found")
     * )
     */
    public function listLocations(int $branchId): JsonResponse
    {
        $locations = $this->service->listLocations($branchId);

        return ApiResponse::success($locations, 'Locations retrieved.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/branches/{branchId}/locations",
     *     tags={"Organisation"},
     *     summary="Create a location under a branch",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="branchId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","code"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="is_active", type="boolean", default=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Location created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function createLocation(Request $request, int $branchId): JsonResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'code'        => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $dto      = CreateLocationDTO::fromArray(array_merge($validated, ['branch_id' => $branchId]));
        $location = $this->service->createLocation($dto);

        return ApiResponse::created($location, 'Location created.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/locations/{id}",
     *     tags={"Organisation"},
     *     summary="Show a single location",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Location data"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function showLocation(int $id): JsonResponse
    {
        $location = $this->service->showLocation($id);

        return ApiResponse::success($location, 'Location retrieved.');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/locations/{id}",
     *     tags={"Organisation"},
     *     summary="Update a location",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Location updated"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updateLocation(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name'        => ['sometimes', 'required', 'string', 'max:255'],
            'code'        => ['sometimes', 'required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $location = $this->service->updateLocation($id, $validated);

        return ApiResponse::success($location, 'Location updated.');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/locations/{id}",
     *     tags={"Organisation"},
     *     summary="Delete a location",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Deleted"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function deleteLocation(int $id): JsonResponse
    {
        $this->service->deleteLocation($id);

        return ApiResponse::noContent();
    }

    // -------------------------------------------------------------------------
    // Department endpoints
    // -------------------------------------------------------------------------

    /**
     * @OA\Get(
     *     path="/api/v1/locations/{locationId}/departments",
     *     tags={"Organisation"},
     *     summary="List departments for a location",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="locationId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="List of departments"),
     *     @OA\Response(response=404, description="Location not found")
     * )
     */
    public function listDepartments(int $locationId): JsonResponse
    {
        $departments = $this->service->listDepartments($locationId);

        return ApiResponse::success($departments, 'Departments retrieved.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/locations/{locationId}/departments",
     *     tags={"Organisation"},
     *     summary="Create a department under a location",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="locationId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","code"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="is_active", type="boolean", default=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Department created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function createDepartment(Request $request, int $locationId): JsonResponse
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'code'      => ['required', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $dto        = CreateDepartmentDTO::fromArray(array_merge($validated, ['location_id' => $locationId]));
        $department = $this->service->createDepartment($dto);

        return ApiResponse::created($department, 'Department created.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/departments/{id}",
     *     tags={"Organisation"},
     *     summary="Show a single department",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Department data"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function showDepartment(int $id): JsonResponse
    {
        $department = $this->service->showDepartment($id);

        return ApiResponse::success($department, 'Department retrieved.');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/departments/{id}",
     *     tags={"Organisation"},
     *     summary="Update a department",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Department updated"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updateDepartment(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name'      => ['sometimes', 'required', 'string', 'max:255'],
            'code'      => ['sometimes', 'required', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $department = $this->service->updateDepartment($id, $validated);

        return ApiResponse::success($department, 'Department updated.');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/departments/{id}",
     *     tags={"Organisation"},
     *     summary="Delete a department",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Deleted"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function deleteDepartment(int $id): JsonResponse
    {
        $this->service->deleteDepartment($id);

        return ApiResponse::noContent();
    }
}
