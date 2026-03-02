<?php

declare(strict_types=1);

namespace Modules\Core\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Domain\Contracts\TenantRepositoryInterface;
use Modules\Core\Domain\ValueObjects\TenantId;
use Illuminate\Support\Facades\Validator;

class TenantController extends Controller
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants
    ) {}

    /**
     * GET /api/v1/tenants
     */
    public function index(): JsonResponse
    {
        $data = $this->tenants->all();

        return response()->json([
            'success' => true,
            'message' => 'Tenants retrieved successfully.',
            'data'    => array_map(fn ($t) => [
                'id'        => $t->getId()->getValue(),
                'name'      => $t->getName(),
                'slug'      => $t->getSlug(),
                'domain'    => $t->getDomain(),
                'plan'      => $t->getPlan(),
                'is_active' => $t->isActive(),
            ], $data),
            'errors'  => null,
        ]);
    }

    /**
     * GET /api/v1/tenants/{id}
     */
    public function show(int $id): JsonResponse
    {
        $tenant = $this->tenants->findById(new TenantId($id));

        if ($tenant === null) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found.',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tenant retrieved successfully.',
            'data'    => [
                'id'        => $tenant->getId()->getValue(),
                'name'      => $tenant->getName(),
                'slug'      => $tenant->getSlug(),
                'domain'    => $tenant->getDomain(),
                'plan'      => $tenant->getPlan(),
                'is_active' => $tenant->isActive(),
            ],
            'errors'  => null,
        ]);
    }

    /**
     * POST /api/v1/tenants
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'   => 'required|string|max:255',
            'slug'   => 'required|string|max:255|unique:tenants,slug|regex:/^[a-z0-9\-]+$/',
            'domain' => 'nullable|string|max:255|unique:tenants,domain',
            'plan'   => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'data'    => null,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $model = \Modules\Core\Infrastructure\Models\Tenant::create([
            'name'      => $request->input('name'),
            'slug'      => $request->input('slug'),
            'domain'    => $request->input('domain'),
            'plan'      => $request->input('plan', 'basic'),
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tenant created successfully.',
            'data'    => $model->toArray(),
            'errors'  => null,
        ], 201);
    }

    /**
     * PUT /api/v1/tenants/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $model = \Modules\Core\Infrastructure\Models\Tenant::find($id);

        if ($model === null) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found.',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'      => 'sometimes|string|max:255',
            'plan'      => 'sometimes|string|max:50',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'data'    => null,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $model->update($request->only(['name', 'plan', 'is_active']));

        return response()->json([
            'success' => true,
            'message' => 'Tenant updated successfully.',
            'data'    => $model->fresh()->toArray(),
            'errors'  => null,
        ]);
    }

    /**
     * DELETE /api/v1/tenants/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $model = \Modules\Core\Infrastructure\Models\Tenant::find($id);

        if ($model === null) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found.',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        $model->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tenant deleted successfully.',
            'data'    => null,
            'errors'  => null,
        ]);
    }
}
