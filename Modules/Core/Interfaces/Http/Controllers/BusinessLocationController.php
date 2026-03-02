<?php

declare(strict_types=1);

namespace Modules\Core\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Infrastructure\Models\BusinessLocation;

/**
 * BusinessLocationController: CRUD for business locations (branches / outlets).
 * Derived from the BusinessLocation model in the PHP_POS reference repository.
 */
class BusinessLocationController extends Controller
{
    /**
     * GET /api/v1/business-locations
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId  = (int) $request->attributes->get('tenant_id');
        $locations = BusinessLocation::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Business locations retrieved successfully.',
            'data'    => $locations->map(fn ($l) => $this->format($l))->all(),
            'errors'  => null,
        ]);
    }

    /**
     * POST /api/v1/business-locations
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:191',
            'address' => 'nullable|string|max:500',
            'city'    => 'nullable|string|max:100',
            'state'   => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'phone'   => 'nullable|string|max:50',
            'email'   => 'nullable|email|max:191',
        ]);

        $location = BusinessLocation::create(array_merge(
            $validated,
            ['tenant_id' => (int) $request->attributes->get('tenant_id'), 'is_active' => true]
        ));

        return response()->json([
            'success' => true,
            'message' => 'Business location created successfully.',
            'data'    => $this->format($location),
            'errors'  => null,
        ], 201);
    }

    /**
     * GET /api/v1/business-locations/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $location = BusinessLocation::withoutGlobalScopes()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->first();

        if ($location === null) {
            return response()->json([
                'success' => false,
                'message' => 'Business location not found.',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Business location retrieved successfully.',
            'data'    => $this->format($location),
            'errors'  => null,
        ]);
    }

    /**
     * PUT /api/v1/business-locations/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $location = BusinessLocation::withoutGlobalScopes()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->first();

        if ($location === null) {
            return response()->json([
                'success' => false,
                'message' => 'Business location not found.',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        $validated = $request->validate([
            'name'      => 'sometimes|string|max:191',
            'address'   => 'nullable|string|max:500',
            'city'      => 'nullable|string|max:100',
            'state'     => 'nullable|string|max:100',
            'country'   => 'nullable|string|max:100',
            'phone'     => 'nullable|string|max:50',
            'email'     => 'nullable|email|max:191',
            'is_active' => 'sometimes|boolean',
        ]);

        $location->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Business location updated successfully.',
            'data'    => $this->format($location->fresh()),
            'errors'  => null,
        ]);
    }

    /**
     * DELETE /api/v1/business-locations/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $location = BusinessLocation::withoutGlobalScopes()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->first();

        if ($location === null) {
            return response()->json([
                'success' => false,
                'message' => 'Business location not found.',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        $location->delete();

        return response()->json([
            'success' => true,
            'message' => 'Business location deleted successfully.',
            'data'    => null,
            'errors'  => null,
        ]);
    }

    private function format(BusinessLocation $location): array
    {
        return [
            'id'        => $location->id,
            'tenant_id' => $location->tenant_id,
            'name'      => $location->name,
            'address'   => $location->address,
            'city'      => $location->city,
            'state'     => $location->state,
            'country'   => $location->country,
            'phone'     => $location->phone,
            'email'     => $location->email,
            'is_active' => $location->is_active,
        ];
    }
}
