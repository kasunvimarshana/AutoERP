<?php

namespace Modules\Configuration\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Configuration\Application\Contracts\CreateOrganizationUnitServiceInterface;
use Modules\Configuration\Application\Contracts\DeleteOrganizationUnitServiceInterface;
use Modules\Configuration\Application\Contracts\OrgUnitTreeServiceInterface;
use Modules\Configuration\Application\Contracts\UpdateOrganizationUnitServiceInterface;
use Modules\Configuration\Application\DTOs\OrganizationUnitData;
use Modules\Configuration\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;
use Modules\Configuration\Infrastructure\Http\Resources\OrganizationUnitResource;

class OrganizationUnitController extends Controller
{
    public function __construct(
        private readonly OrganizationUnitRepositoryInterface $repository,
        private readonly CreateOrganizationUnitServiceInterface $createService,
        private readonly UpdateOrganizationUnitServiceInterface $updateService,
        private readonly DeleteOrganizationUnitServiceInterface $deleteService,
        private readonly OrgUnitTreeServiceInterface $treeService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID', 1);
        return response()->json($this->repository->findAll($tenantId));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'integer'],
            'name'      => ['required', 'string', 'max:255'],
            'code'      => ['required', 'string', 'max:50'],
            'type'      => ['required', 'string', 'max:50'],
            'parent_id' => ['nullable', 'integer'],
            'address'   => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $data = new OrganizationUnitData(
            tenantId: $validated['tenant_id'],
            name: $validated['name'],
            code: $validated['code'],
            type: $validated['type'],
            parentId: $validated['parent_id'] ?? null,
            address: $validated['address'] ?? null,
            isActive: $validated['is_active'] ?? true,
        );
        return response()->json(new OrganizationUnitResource($this->createService->execute($data)), 201);
    }

    public function show(int $id): JsonResponse
    {
        $unit = $this->repository->findById($id);
        if (!$unit) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json(new OrganizationUnitResource($unit));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $unit = $this->repository->findById($id);
        if (!$unit) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $data = $request->validate([
            'name'      => ['sometimes', 'string'],
            'code'      => ['sometimes', 'string'],
            'type'      => ['sometimes', 'string'],
            'parent_id' => ['nullable', 'integer'],
            'address'   => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        return response()->json(new OrganizationUnitResource($this->updateService->execute($unit, $data)));
    }

    public function destroy(int $id): JsonResponse
    {
        $unit = $this->repository->findById($id);
        if (!$unit) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $this->deleteService->execute($unit);
        return response()->json(null, 204);
    }

    public function tree(Request $request): JsonResponse
    {
        $tenantId = (int) $request->query('tenant_id', 0);
        return response()->json($this->treeService->buildTree($tenantId));
    }
}
