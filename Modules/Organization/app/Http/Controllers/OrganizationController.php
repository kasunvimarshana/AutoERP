<?php

declare(strict_types=1);

namespace Modules\Organization\Http\Controllers;

use App\Core\Traits\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Organization\Requests\StoreOrganizationRequest;
use Modules\Organization\Requests\UpdateOrganizationRequest;
use Modules\Organization\Resources\OrganizationResource;
use Modules\Organization\Services\OrganizationService;

/**
 * Organization Controller
 *
 * Handles HTTP requests for Organization operations
 * Follows Controller → Service → Repository pattern
 */
class OrganizationController extends Controller
{
    use ApiResponse;

    /**
     * OrganizationController constructor
     */
    public function __construct(
        private readonly OrganizationService $organizationService
    ) {}

    /**
     * Display a listing of organizations
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'paginate' => $request->boolean('paginate', true),
            'per_page' => $request->integer('per_page', 15),
        ];

        $organizations = $this->organizationService->getAll($filters);

        return $this->successResponse(
            OrganizationResource::collection($organizations),
            __('organization::messages.organizations_retrieved')
        );
    }

    /**
     * Store a newly created organization
     */
    public function store(StoreOrganizationRequest $request): JsonResponse
    {
        $organization = $this->organizationService->create($request->validated());

        return $this->createdResponse(
            new OrganizationResource($organization),
            __('organization::messages.organization_created')
        );
    }

    /**
     * Display the specified organization
     */
    public function show(int $id): JsonResponse
    {
        $organization = $this->organizationService->getById($id);

        return $this->successResponse(
            new OrganizationResource($organization),
            __('organization::messages.organization_retrieved')
        );
    }

    /**
     * Update the specified organization
     */
    public function update(UpdateOrganizationRequest $request, int $id): JsonResponse
    {
        $organization = $this->organizationService->update($id, $request->validated());

        return $this->successResponse(
            new OrganizationResource($organization),
            __('organization::messages.organization_updated')
        );
    }

    /**
     * Remove the specified organization
     */
    public function destroy(int $id): JsonResponse
    {
        $this->organizationService->delete($id);

        return $this->successResponse(
            null,
            __('organization::messages.organization_deleted')
        );
    }

    /**
     * Search organizations
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('query', '');
        $organizations = $this->organizationService->search($query);

        return $this->successResponse(
            OrganizationResource::collection($organizations),
            __('organization::messages.search_results')
        );
    }
}
