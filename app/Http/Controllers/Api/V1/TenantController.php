<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\TenantServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CreateTenantRequest;
use App\Http\Requests\Tenant\UpdateTenantRequest;
use App\Http\Resources\TenantResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TenantController extends Controller
{
    public function __construct(
        private readonly TenantServiceInterface $tenantService
    ) {}

    public function index(Request $request): ResourceCollection
    {
        $perPage = min((int) $request->query('per_page', 15), 100);

        return TenantResource::collection($this->tenantService->paginate($perPage));
    }

    public function store(CreateTenantRequest $request): JsonResponse
    {
        $tenant = $this->tenantService->create($request->validated());

        return (new TenantResource($tenant))->response()->setStatusCode(201);
    }

    public function update(UpdateTenantRequest $request, string $id): JsonResponse
    {
        $tenant = $this->tenantService->update($id, $request->validated());

        return (new TenantResource($tenant))->response();
    }

    public function suspend(string $id): JsonResponse
    {
        $tenant = $this->tenantService->suspend($id);

        return (new TenantResource($tenant))->response();
    }

    public function activate(string $id): JsonResponse
    {
        $tenant = $this->tenantService->activate($id);

        return (new TenantResource($tenant))->response();
    }
}
