<?php

declare(strict_types=1);

namespace Modules\Organization\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Organization\Entities\Organization;
use Modules\Organization\Http\Requests\MoveOrganizationRequest;
use Modules\Organization\Services\OrganizationHierarchyService;

/**
 * Organization Hierarchy Controller
 *
 * Provides endpoints for hierarchical organization operations:
 * - Ancestors and descendants retrieval
 * - Organization movement (re-parenting)
 * - Breadcrumb and path generation
 * - Access validation
 */
class OrganizationHierarchyController extends Controller
{
    public function __construct(
        private OrganizationHierarchyService $hierarchyService
    ) {}

    /**
     * Get ancestors of an organization (excluding self).
     *
     * @param int $id Organization ID
     * @return JsonResponse
     */
    public function ancestors(int $id): JsonResponse
    {
        $organization = Organization::find($id);

        if (!$organization) {
            return response()->json([
                'message' => 'Organization not found'
            ], 404);
        }

        $this->authorize('view', $organization);

        $ancestors = $this->hierarchyService->getAncestors($id);

        return response()->json([
            'data' => $ancestors->map(function ($org) {
                return [
                    'id' => $org->id,
                    'code' => $org->code,
                    'name' => $org->getTranslation('name'),
                    'level' => $org->level,
                    'organization_type' => $org->organization_type,
                ];
            })
        ]);
    }

    /**
     * Get descendants of an organization (excluding self).
     *
     * @param int $id Organization ID
     * @return JsonResponse
     */
    public function descendants(int $id): JsonResponse
    {
        $organization = Organization::find($id);

        if (!$organization) {
            return response()->json([
                'message' => 'Organization not found'
            ], 404);
        }

        $this->authorize('view', $organization);

        $descendants = $this->hierarchyService->getDescendants($id);

        return response()->json([
            'data' => $descendants->map(function ($org) {
                return [
                    'id' => $org->id,
                    'code' => $org->code,
                    'name' => $org->getTranslation('name'),
                    'level' => $org->level,
                    'organization_type' => $org->organization_type,
                    'parent_id' => $org->parent_id,
                ];
            })
        ]);
    }

    /**
     * Get immediate children of an organization.
     *
     * @param int $id Organization ID
     * @return JsonResponse
     */
    public function children(int $id): JsonResponse
    {
        $organization = Organization::find($id);

        if (!$organization) {
            return response()->json([
                'message' => 'Organization not found'
            ], 404);
        }

        $this->authorize('view', $organization);

        $children = $this->hierarchyService->getChildren($id);

        return response()->json([
            'data' => $children->map(function ($org) {
                return [
                    'id' => $org->id,
                    'code' => $org->code,
                    'name' => $org->getTranslation('name'),
                    'level' => $org->level,
                    'organization_type' => $org->organization_type,
                    'status' => $org->status,
                ];
            })
        ]);
    }

    /**
     * Get siblings of an organization.
     *
     * @param int $id Organization ID
     * @return JsonResponse
     */
    public function siblings(int $id, Request $request): JsonResponse
    {
        $organization = Organization::find($id);

        if (!$organization) {
            return response()->json([
                'message' => 'Organization not found'
            ], 404);
        }

        $this->authorize('view', $organization);

        $includeSelf = $request->boolean('include_self', false);
        $siblings = $this->hierarchyService->getSiblings($id, $includeSelf);

        return response()->json([
            'data' => $siblings->map(function ($org) {
                return [
                    'id' => $org->id,
                    'code' => $org->code,
                    'name' => $org->getTranslation('name'),
                    'level' => $org->level,
                    'organization_type' => $org->organization_type,
                ];
            })
        ]);
    }

    /**
     * Get full tree (ancestors, self, descendants).
     *
     * @param int $id Organization ID
     * @return JsonResponse
     */
    public function fullTree(int $id): JsonResponse
    {
        $organization = Organization::find($id);

        if (!$organization) {
            return response()->json([
                'message' => 'Organization not found'
            ], 404);
        }

        $this->authorize('viewHierarchy', $organization);

        $tree = $this->hierarchyService->getFullTree($id);

        return response()->json([
            'data' => $tree->map(function ($org) {
                return [
                    'id' => $org->id,
                    'code' => $org->code,
                    'name' => $org->getTranslation('name'),
                    'level' => $org->level,
                    'organization_type' => $org->organization_type,
                    'parent_id' => $org->parent_id,
                ];
            })
        ]);
    }

    /**
     * Get organization breadcrumb (path from root).
     *
     * @param int $id Organization ID
     * @return JsonResponse
     */
    public function breadcrumb(int $id): JsonResponse
    {
        $organization = Organization::find($id);

        if (!$organization) {
            return response()->json([
                'message' => 'Organization not found'
            ], 404);
        }

        $this->authorize('view', $organization);

        $breadcrumb = $this->hierarchyService->getBreadcrumb($id);

        return response()->json([
            'data' => $breadcrumb
        ]);
    }

    /**
     * Get root organizations for the current tenant.
     *
     * @return JsonResponse
     */
    public function roots(): JsonResponse
    {
        $tenantId = session('tenant_id') ?? auth()->user()->tenant_id;

        if (!$tenantId) {
            return response()->json([
                'message' => 'Tenant context required'
            ], 403);
        }

        $roots = $this->hierarchyService->getRootOrganizations($tenantId);

        return response()->json([
            'data' => $roots->map(function ($org) {
                return [
                    'id' => $org->id,
                    'code' => $org->code,
                    'name' => $org->getTranslation('name'),
                    'organization_type' => $org->organization_type,
                    'status' => $org->status,
                ];
            })
        ]);
    }

    /**
     * Move an organization to a new parent.
     *
     * @param MoveOrganizationRequest $request
     * @param int $id Organization ID to move
     * @return JsonResponse
     */
    public function move(MoveOrganizationRequest $request, int $id): JsonResponse
    {
        $organization = Organization::find($id);

        if (!$organization) {
            return response()->json([
                'message' => 'Organization not found'
            ], 404);
        }

        $this->authorize('move', $organization);

        try {
            $newParentId = $request->input('parent_id');
            $moved = $this->hierarchyService->moveOrganization($id, $newParentId);

            return response()->json([
                'message' => 'Organization moved successfully',
                'data' => [
                    'id' => $moved->id,
                    'code' => $moved->code,
                    'name' => $moved->getTranslation('name'),
                    'parent_id' => $moved->parent_id,
                    'level' => $moved->level,
                    'path' => $moved->path,
                ]
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to move organization',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get accessible organizations for the current user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function accessible(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'Authentication required'
            ], 401);
        }

        $visibility = $request->input('visibility', 'own');
        $allowedVisibilities = ['own', 'children', 'tree', 'tenant'];

        if (!in_array($visibility, $allowedVisibilities)) {
            return response()->json([
                'message' => 'Invalid visibility parameter',
                'allowed' => $allowedVisibilities
            ], 422);
        }

        $organizations = $this->hierarchyService->getAccessibleOrganizations($user->id, $visibility);

        return response()->json([
            'data' => $organizations->map(function ($org) {
                return [
                    'id' => $org->id,
                    'code' => $org->code,
                    'name' => $org->getTranslation('name'),
                    'level' => $org->level,
                    'organization_type' => $org->organization_type,
                    'status' => $org->status,
                    'parent_id' => $org->parent_id,
                ];
            }),
            'visibility' => $visibility,
            'count' => $organizations->count()
        ]);
    }

    /**
     * Check if user has access to an organization.
     *
     * @param Request $request
     * @param int $id Organization ID
     * @return JsonResponse
     */
    public function checkAccess(Request $request, int $id): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'Authentication required'
            ], 401);
        }

        $organization = Organization::find($id);

        if (!$organization) {
            return response()->json([
                'message' => 'Organization not found'
            ], 404);
        }

        $visibility = $request->input('visibility', 'own');
        $hasAccess = $this->hierarchyService->hasAccess($user->id, $id, $visibility);

        return response()->json([
            'has_access' => $hasAccess,
            'organization_id' => $id,
            'visibility' => $visibility
        ]);
    }
}
