<?php

namespace App\Modules\TenantManagement\Http\Controllers;

use App\Core\Base\BaseController;
use OpenApi\Attributes as OA;
use App\Modules\TenantManagement\Services\TenantService;
use App\Modules\TenantManagement\Http\Requests\StoreTenantRequest;
use App\Modules\TenantManagement\Http\Requests\UpdateTenantRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TenantController extends BaseController
{
    protected TenantService $tenantService;

    public function __construct(TenantService $tenantService)
    {
        $this->tenantService = $tenantService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $criteria = [
                'search' => $request->input('search'),
                'status' => $request->input('status'),
                'subscription_status' => $request->input('subscription_status'),
                'per_page' => $request->input('per_page', 15),
            ];

            $tenants = $this->tenantService->search($criteria);
            return $this->success($tenants);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function store(StoreTenantRequest $request): JsonResponse
    {
        try {
            $tenant = $this->tenantService->create($request->validated());
            return $this->created($tenant, 'Tenant created successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $tenant = $this->tenantService->findById($id);
            
            if (!$tenant) {
                return $this->notFound('Tenant not found');
            }

            return $this->success($tenant);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function update(UpdateTenantRequest $request, int $id): JsonResponse
    {
        try {
            $tenant = $this->tenantService->update($id, $request->validated());
            return $this->success($tenant, 'Tenant updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->tenantService->delete($id);
            return $this->success(null, 'Tenant deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function activate(int $id): JsonResponse
    {
        try {
            $tenant = $this->tenantService->activate($id);
            return $this->success($tenant, 'Tenant activated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function suspend(int $id): JsonResponse
    {
        try {
            $tenant = $this->tenantService->suspend($id);
            return $this->success($tenant, 'Tenant suspended successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function updateSubscription(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'subscription_status' => 'required|in:trial,active,expired,cancelled',
                'subscription_plan' => 'nullable|string',
                'subscription_started_at' => 'nullable|date',
                'subscription_expires_at' => 'nullable|date',
            ]);

            $tenant = $this->tenantService->updateSubscription($id, $request->all());
            return $this->success($tenant, 'Subscription updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function renewSubscription(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'months' => 'nullable|integer|min:1|max:24',
            ]);

            $months = $request->input('months', 1);
            $tenant = $this->tenantService->renewSubscription($id, $months);
            return $this->success($tenant, 'Subscription renewed successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
