<?php

namespace App\Http\Controllers\API;

use App\Services\TenantService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * Tenant Controller
 * 
 * RESTful API endpoints for tenant management.
 * Handles CRUD operations and tenant lifecycle management.
 */
class TenantController extends APIController
{
    /**
     * @var TenantService
     */
    protected TenantService $tenantService;

    /**
     * Constructor
     *
     * @param TenantService $tenantService
     */
    public function __construct(TenantService $tenantService)
    {
        $this->tenantService = $tenantService;
    }

    /**
     * Display a listing of tenants
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $filters = $request->only(['search', 'status', 'sort_by', 'sort_direction']);
            
            $tenants = $this->tenantService->getPaginated($perPage, $filters);
            
            return $this->successResponse($tenants, 'Tenants retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve tenants: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created tenant
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:tenants,slug',
            'domain' => 'nullable|string|max:255|unique:tenants,domain',
            'subdomain' => 'nullable|string|max:255|unique:tenants,subdomain',
            'status' => 'nullable|in:trial,active,suspended,cancelled',
            'settings' => 'nullable|array',
            'trial_ends_at' => 'nullable|date',
            'subscription_ends_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        try {
            $tenant = $this->tenantService->create($request->all());
            
            return $this->createdResponse($tenant, 'Tenant created successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create tenant: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified tenant
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $tenant = $this->tenantService->getById($id);
            
            return $this->successResponse($tenant, 'Tenant retrieved successfully');
        } catch (\Exception $e) {
            return $this->notFoundResponse('Tenant not found');
        }
    }

    /**
     * Update the specified tenant
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:tenants,slug,' . $id,
            'domain' => 'sometimes|string|max:255|unique:tenants,domain,' . $id,
            'subdomain' => 'sometimes|string|max:255|unique:tenants,subdomain,' . $id,
            'status' => 'sometimes|in:trial,active,suspended,cancelled',
            'settings' => 'sometimes|array',
            'trial_ends_at' => 'sometimes|nullable|date',
            'subscription_ends_at' => 'sometimes|nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        try {
            $tenant = $this->tenantService->update($id, $request->all());
            
            return $this->successResponse($tenant, 'Tenant updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update tenant: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified tenant
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->tenantService->delete($id);
            
            return $this->noContentResponse();
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete tenant: ' . $e->getMessage());
        }
    }

    /**
     * Activate a tenant
     *
     * @param int $id
     * @return JsonResponse
     */
    public function activate(int $id): JsonResponse
    {
        try {
            $tenant = $this->tenantService->activate($id);
            
            return $this->successResponse($tenant, 'Tenant activated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to activate tenant: ' . $e->getMessage());
        }
    }

    /**
     * Suspend a tenant
     *
     * @param int $id
     * @return JsonResponse
     */
    public function suspend(int $id): JsonResponse
    {
        try {
            $tenant = $this->tenantService->suspend($id);
            
            return $this->successResponse($tenant, 'Tenant suspended successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to suspend tenant: ' . $e->getMessage());
        }
    }

    /**
     * Cancel a tenant
     *
     * @param int $id
     * @return JsonResponse
     */
    public function cancel(int $id): JsonResponse
    {
        try {
            $tenant = $this->tenantService->cancel($id);
            
            return $this->successResponse($tenant, 'Tenant cancelled successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to cancel tenant: ' . $e->getMessage());
        }
    }

    /**
     * Extend trial period
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function extendTrial(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'days' => 'required|integer|min:1|max:365',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        try {
            $tenant = $this->tenantService->extendTrial($id, $request->get('days'));
            
            return $this->successResponse($tenant, 'Trial period extended successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to extend trial: ' . $e->getMessage());
        }
    }
}
