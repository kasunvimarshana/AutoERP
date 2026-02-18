<?php

declare(strict_types=1);

namespace Modules\Organization\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Organization\Http\Requests\StoreOrganizationRequest;
use Modules\Organization\Http\Requests\UpdateOrganizationRequest;
use Modules\Organization\Http\Resources\OrganizationResource;
use Modules\Organization\Services\OrganizationService;
use Modules\Organization\Repositories\Contracts\OrganizationRepositoryInterface;

class OrganizationController extends Controller
{
    public function __construct(
        private OrganizationService $organizationService,
        private OrganizationRepositoryInterface $organizationRepository
    ) {}

    /**
     * Display a listing of organizations.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $organizations = $this->organizationRepository->paginate($perPage);

        return response()->json(OrganizationResource::collection($organizations));
    }

    /**
     * Store a newly created organization.
     */
    public function store(StoreOrganizationRequest $request): JsonResponse
    {
        try {
            $organization = $this->organizationService->createOrganization($request->validated());
            
            return response()->json(
                new OrganizationResource($organization),
                201
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create organization',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Display the specified organization.
     */
    public function show(int $id): JsonResponse
    {
        $organization = $this->organizationRepository->findById($id);

        if (!$organization) {
            return response()->json([
                'message' => 'Organization not found'
            ], 404);
        }

        return response()->json(new OrganizationResource($organization));
    }

    /**
     * Update the specified organization.
     */
    public function update(UpdateOrganizationRequest $request, int $id): JsonResponse
    {
        try {
            $organization = $this->organizationService->updateOrganization($id, $request->validated());
            
            return response()->json(new OrganizationResource($organization));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update organization',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Remove the specified organization.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->organizationService->deleteOrganization($id);
            
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete organization',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get children of an organization.
     */
    public function children(int $id): JsonResponse
    {
        $organization = $this->organizationRepository->findById($id);

        if (!$organization) {
            return response()->json([
                'message' => 'Organization not found'
            ], 404);
        }

        return response()->json(
            OrganizationResource::collection($organization->children)
        );
    }

    /**
     * Get full hierarchy tree.
     */
    public function hierarchy(Request $request, ?int $id = null): JsonResponse
    {
        try {
            $tree = $this->organizationService->getHierarchyTree($id);
            
            return response()->json(
                OrganizationResource::collection($tree)
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve hierarchy',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get all descendants of an organization.
     */
    public function descendants(int $id): JsonResponse
    {
        $organization = $this->organizationRepository->findById($id);

        if (!$organization) {
            return response()->json([
                'message' => 'Organization not found'
            ], 404);
        }

        return response()->json(
            OrganizationResource::collection($organization->descendants())
        );
    }
}
