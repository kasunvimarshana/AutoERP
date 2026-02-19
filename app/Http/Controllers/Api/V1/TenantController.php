<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\TenantServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CreateTenantRequest;
use App\Http\Requests\Tenant\UpdateTenantRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function __construct(
        private readonly TenantServiceInterface $tenantService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 15), 100);

        return response()->json($this->tenantService->paginate($perPage));
    }

    public function store(CreateTenantRequest $request): JsonResponse
    {
        $tenant = $this->tenantService->create($request->validated());

        return response()->json($tenant, 201);
    }

    public function update(UpdateTenantRequest $request, string $id): JsonResponse
    {
        $tenant = $this->tenantService->update($id, $request->validated());

        return response()->json($tenant);
    }

    public function suspend(string $id): JsonResponse
    {
        $tenant = $this->tenantService->suspend($id);

        return response()->json($tenant);
    }

    public function activate(string $id): JsonResponse
    {
        $tenant = $this->tenantService->activate($id);

        return response()->json($tenant);
    }
}
