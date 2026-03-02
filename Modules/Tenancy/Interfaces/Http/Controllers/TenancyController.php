<?php

declare(strict_types=1);

namespace Modules\Tenancy\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Modules\Core\Interfaces\Http\Resources\ApiResponse;
use Modules\Tenancy\Application\DTOs\CreateTenantDTO;
use Modules\Tenancy\Application\Services\TenancyService;

/**
 * Tenancy controller.
 *
 * Input validation, authorization checks, and response formatting ONLY.
 * No business logic — all delegated to TenancyService.
 *
 * @OA\Tag(name="Tenancy", description="Tenant management endpoints")
 */
class TenancyController extends Controller
{
    public function __construct(private readonly TenancyService $service) {}

    /**
     * @OA\Get(
     *     path="/api/v1/tenants",
     *     tags={"Tenancy"},
     *     summary="List tenants (paginated)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Paginated list of tenants"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 15), 100);
        $paginator = $this->service->list($perPage);

        return ApiResponse::paginated($paginator, 'Tenants retrieved.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/tenants",
     *     tags={"Tenancy"},
     *     summary="Create a new tenant",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","slug"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="slug", type="string"),
     *             @OA\Property(property="domain", type="string", nullable=true),
     *             @OA\Property(property="is_active", type="boolean", default=true),
     *             @OA\Property(property="pharma_compliance_mode", type="boolean", default=false)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Tenant created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'                   => ['required', 'string', 'max:255'],
            'slug'                   => ['required', 'string', 'max:100', Rule::unique('tenants', 'slug')],
            'domain'                 => ['nullable', 'string', 'max:255'],
            'is_active'              => ['nullable', 'boolean'],
            'pharma_compliance_mode' => ['nullable', 'boolean'],
        ]);

        $dto = CreateTenantDTO::fromArray($validated);
        $tenant = $this->service->create($dto);

        return ApiResponse::created($tenant, 'Tenant created.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/tenants/{id}",
     *     tags={"Tenancy"},
     *     summary="Get a single tenant",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Tenant data"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $tenant = $this->service->show($id);

        return ApiResponse::success($tenant, 'Tenant retrieved.');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/tenants/{id}",
     *     tags={"Tenancy"},
     *     summary="Update a tenant",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="slug", type="string"),
     *             @OA\Property(property="domain", type="string", nullable=true),
     *             @OA\Property(property="is_active", type="boolean"),
     *             @OA\Property(property="pharma_compliance_mode", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Tenant updated"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name'                   => ['sometimes', 'required', 'string', 'max:255'],
            'slug'                   => ['sometimes', 'required', 'string', 'max:100', Rule::unique('tenants', 'slug')->ignore($id)],
            'domain'                 => ['nullable', 'string', 'max:255'],
            'is_active'              => ['nullable', 'boolean'],
            'pharma_compliance_mode' => ['nullable', 'boolean'],
        ]);

        $tenant = $this->service->update($id, $validated);

        return ApiResponse::success($tenant, 'Tenant updated.');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/tenants/{id}",
     *     tags={"Tenancy"},
     *     summary="Delete a tenant",
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
}
