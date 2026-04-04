<?php
namespace Modules\Tenant\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Tenant\Application\Contracts\CreateTenantServiceInterface;
use Modules\Tenant\Application\Contracts\DeleteTenantServiceInterface;
use Modules\Tenant\Application\Contracts\UpdateTenantServiceInterface;
use Modules\Tenant\Application\DTOs\TenantData;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;
use Modules\Tenant\Infrastructure\Http\Requests\StoreTenantRequest;
use Modules\Tenant\Infrastructure\Http\Requests\UpdateTenantRequest;
use Modules\Tenant\Infrastructure\Http\Resources\TenantResource;

class TenantController extends Controller
{
    public function __construct(
        private readonly TenantRepositoryInterface $repository,
        private readonly CreateTenantServiceInterface $createService,
        private readonly UpdateTenantServiceInterface $updateService,
        private readonly DeleteTenantServiceInterface $deleteService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenants = $this->repository->findAll($request->only(['status', 'plan']));
        return response()->json($tenants);
    }

    public function store(StoreTenantRequest $request): JsonResponse
    {
        $data = new TenantData(...$request->validated());
        $tenant = $this->createService->execute($data);
        return response()->json(new TenantResource($tenant), 201);
    }

    public function show(int $id): JsonResponse
    {
        $tenant = $this->repository->findById($id);
        if (!$tenant) return response()->json(['message' => 'Not found'], 404);
        return response()->json(new TenantResource($tenant));
    }

    public function update(UpdateTenantRequest $request, int $id): JsonResponse
    {
        $tenant = $this->repository->findById($id);
        if (!$tenant) return response()->json(['message' => 'Not found'], 404);
        $data = new TenantData(...$request->validated());
        $updated = $this->updateService->execute($tenant, $data);
        return response()->json(new TenantResource($updated));
    }

    public function destroy(int $id): JsonResponse
    {
        $tenant = $this->repository->findById($id);
        if (!$tenant) return response()->json(['message' => 'Not found'], 404);
        $this->deleteService->execute($tenant);
        return response()->json(null, 204);
    }
}
