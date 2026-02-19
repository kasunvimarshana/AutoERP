<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Controllers;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Modules\Tenant\Http\Requests\MoveOrganizationRequest;
use Modules\Tenant\Http\Requests\StoreOrganizationRequest;
use Modules\Tenant\Http\Requests\UpdateOrganizationRequest;
use Modules\Tenant\Http\Resources\OrganizationResource;
use Modules\Tenant\Models\Organization;
use Modules\Tenant\Repositories\OrganizationRepository;
use Modules\Tenant\Services\OrganizationService;
use Modules\Tenant\Services\TenantContext;

/**
 * OrganizationController
 *
 * Manages organization CRUD operations with hierarchical support
 */
class OrganizationController extends ApiController
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected OrganizationService $organizationService,
        protected OrganizationRepository $organizationRepository
    ) {}

    /**
     * Display a listing of organizations
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Organization::class);
        $tenantId = $this->tenantContext->getCurrentTenantId();
        if (! $tenantId) {
            return $this->error('Tenant context is required', 400);
        }
        $filters = [
            'active' => request()->input('active'),
            'type' => request()->input('type'),
            'parent_id' => request()->input('parent_id'),
            'search' => request()->input('search'),
            'sort_by' => request()->input('sort_by', 'name'),
            'sort_order' => request()->input('sort_order', 'asc'),
        ];
        $perPage = request()->input('per_page', 15);
        $organizations = $this->organizationRepository->findWithFilters($tenantId, $filters, $perPage);
        return $this->paginated($organizations, OrganizationResource::class);
    }

    /**
     * Store a newly created organization
     */
    public function store(StoreOrganizationRequest $request): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        if (! $tenantId) {
            return $this->error('Tenant context is required', 400);
        }
        $data = array_merge($request->validated(), ['tenant_id' => $tenantId]);
        $organization = $this->organizationService->createOrganization($data);
        return $this->created(new OrganizationResource($organization), 'Organization created successfully');
    }

    /**
     * Display the specified organization
     */
    public function show(Organization $organization): JsonResponse
    {
        $this->authorize('view', $organization);
        $tenantId = $this->tenantContext->getCurrentTenantId();
        if ($organization->tenant_id !== $tenantId) {
            return $this->forbidden('Organization does not belong to current tenant');
        }
        $organization->load(['parent', 'children']);
        return $this->success(new OrganizationResource($organization));
    }

    /**
     * Update the specified organization
     */
    public function update(UpdateOrganizationRequest $request, Organization $organization): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        if ($organization->tenant_id !== $tenantId) {
            return $this->forbidden('Organization does not belong to current tenant');
        }
        $updatedOrganization = $this->organizationService->updateOrganization($organization->id, $request->validated());
        return $this->success(new OrganizationResource($updatedOrganization), 'Organization updated successfully');
    }

    /**
     * Remove the specified organization (soft delete)
     */
    public function destroy(Organization $organization): JsonResponse
    {
        $this->authorize('delete', $organization);
        $tenantId = $this->tenantContext->getCurrentTenantId();
        if ($organization->tenant_id !== $tenantId) {
            return $this->forbidden('Organization does not belong to current tenant');
        }
        $this->organizationService->deleteOrganization($organization->id);
        return $this->success(null, 'Organization deleted successfully');
    }

    /**
     * Get child organizations of the specified organization
     */
    public function children(Organization $organization): JsonResponse
    {
        $this->authorize('view', $organization);
        $tenantId = $this->tenantContext->getCurrentTenantId();
        if ($organization->tenant_id !== $tenantId) {
            return $this->forbidden('Organization does not belong to current tenant');
        }
        $children = $organization->children()
            ->when(request('active') !== null, function ($query) {
                $query->where('is_active', request('active') === 'true');
            })
            ->with(['children'])
            ->orderBy('name')
            ->get();
        return $this->success(OrganizationResource::collection($children));
    }

    /**
     * Get ancestors (parent hierarchy) of the specified organization
     */
    public function ancestors(Organization $organization): JsonResponse
    {
        $this->authorize('view', $organization);
        $tenantId = $this->tenantContext->getCurrentTenantId();
        if ($organization->tenant_id !== $tenantId) {
            return $this->forbidden('Organization does not belong to current tenant');
        }
        $ancestors = $organization->ancestors();
        return $this->success(OrganizationResource::collection($ancestors));
    }

    /**
     * Get descendants (full tree below) of the specified organization
     */
    public function descendants(Organization $organization): JsonResponse
    {
        $this->authorize('view', $organization);
        $tenantId = $this->tenantContext->getCurrentTenantId();
        if ($organization->tenant_id !== $tenantId) {
            return $this->forbidden('Organization does not belong to current tenant');
        }
        $descendants = $organization->descendants();
        return $this->success(OrganizationResource::collection($descendants));
    }

    /**
     * Move organization to a different parent
     */
    public function move(MoveOrganizationRequest $request, Organization $organization): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        if ($organization->tenant_id !== $tenantId) {
            return $this->forbidden('Organization does not belong to current tenant');
        }
        $newParentId = $request->input('parent_id');
        $movedOrganization = $this->organizationService->moveOrganization($organization->id, $newParentId);
        return $this->success(new OrganizationResource($movedOrganization), 'Organization moved successfully');
    }

    /**
     * Restore a soft-deleted organization
     */
    public function restore(string $id): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $organization = $this->organizationRepository->findWithTrashed($id);
        if (! $organization || $organization->tenant_id !== $tenantId) {
            return $this->notFound('Organization not found');
        }
        $this->authorize('restore', $organization);
        $restoredOrganization = $this->organizationService->restoreOrganization($id);
        return $this->success(new OrganizationResource($restoredOrganization), 'Organization restored successfully');
    }
}
