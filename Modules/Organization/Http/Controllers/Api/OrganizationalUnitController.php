<?php

declare(strict_types=1);

namespace Modules\Organization\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Organization\Entities\OrganizationalUnit;
use Modules\Organization\Http\Requests\StoreOrganizationalUnitRequest;
use Modules\Organization\Http\Requests\UpdateOrganizationalUnitRequest;
use Modules\Organization\Http\Resources\OrganizationalUnitResource;
use Modules\Organization\Services\OrganizationalUnitService;

class OrganizationalUnitController extends Controller
{
    public function __construct(
        private OrganizationalUnitService $unitService
    ) {}

    /**
     * Display a listing of organizational units.
     */
    public function index(Request $request): ResourceCollection
    {
        $perPage = $request->input('per_page', 15);
        
        // Support filtering by organization, location, type, status
        $query = OrganizationalUnit::query();
        
        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->input('organization_id'));
        }
        
        if ($request->has('location_id')) {
            $query->where('location_id', $request->input('location_id'));
        }
        
        if ($request->has('unit_type')) {
            $query->where('unit_type', $request->input('unit_type'));
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        
        if ($request->has('manager_id')) {
            $query->where('manager_id', $request->input('manager_id'));
        }
        
        // Support search
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                  ->orWhereRaw("JSON_EXTRACT(name, '$.en') LIKE ?", ["%{$search}%"]);
            });
        }
        
        // Support eager loading
        $with = [];
        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            foreach ($includes as $include) {
                if (in_array($include, ['organization', 'location', 'parent', 'children', 'manager'])) {
                    $with[] = $include;
                }
            }
        }
        
        if (!empty($with)) {
            $query->with($with);
        }
        
        $units = $query->paginate($perPage);
        
        return OrganizationalUnitResource::collection($units);
    }

    /**
     * Store a newly created organizational unit.
     */
    public function store(StoreOrganizationalUnitRequest $request): JsonResponse
    {
        try {
            $unit = $this->unitService->createUnit($request->validated());
            
            return (new OrganizationalUnitResource($unit))
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create organizational unit',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Display the specified organizational unit.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $unit = OrganizationalUnit::find($id);
        
        if (!$unit) {
            return response()->json([
                'message' => 'Organizational unit not found'
            ], 404);
        }
        
        // Support eager loading
        $with = [];
        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            foreach ($includes as $include) {
                if (in_array($include, ['organization', 'location', 'parent', 'children', 'manager'])) {
                    $with[] = $include;
                }
            }
        }
        
        if (!empty($with)) {
            $unit->load($with);
        }
        
        return response()->json(new OrganizationalUnitResource($unit));
    }

    /**
     * Update the specified organizational unit.
     */
    public function update(UpdateOrganizationalUnitRequest $request, int $id): JsonResponse
    {
        try {
            $unit = $this->unitService->updateUnit($id, $request->validated());
            
            return response()->json(new OrganizationalUnitResource($unit));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update organizational unit',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Remove the specified organizational unit.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->unitService->deleteUnit($id);
            
            return response()->json([
                'message' => 'Organizational unit deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete organizational unit',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get children of an organizational unit.
     */
    public function children(int $id): JsonResponse
    {
        $unit = OrganizationalUnit::find($id);
        
        if (!$unit) {
            return response()->json([
                'message' => 'Organizational unit not found'
            ], 404);
        }
        
        $children = $unit->children;
        
        return response()->json(OrganizationalUnitResource::collection($children));
    }

    /**
     * Get hierarchy (unit with nested children).
     */
    public function hierarchy(int $id): JsonResponse
    {
        try {
            $unit = $this->unitService->getHierarchy($id);
            
            return response()->json(new OrganizationalUnitResource($unit));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get organizational unit hierarchy',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get all descendants of an organizational unit.
     */
    public function descendants(int $id): JsonResponse
    {
        $unit = OrganizationalUnit::find($id);
        
        if (!$unit) {
            return response()->json([
                'message' => 'Organizational unit not found'
            ], 404);
        }
        
        $descendants = $unit->descendants();
        
        return response()->json(OrganizationalUnitResource::collection($descendants));
    }

    /**
     * Get organizational tree for a specific organization.
     */
    public function organizationTree(int $organizationId): JsonResponse
    {
        try {
            $tree = $this->unitService->getOrganizationTree($organizationId);
            
            return response()->json(OrganizationalUnitResource::collection($tree));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get organization tree',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
