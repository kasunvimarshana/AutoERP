<?php
namespace Modules\Pricing\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Pricing\Application\Contracts\CreateTaxGroupServiceInterface;
use Modules\Pricing\Application\DTOs\TaxGroupData;
use Modules\Pricing\Domain\RepositoryInterfaces\TaxGroupRepositoryInterface;
use Modules\Pricing\Infrastructure\Http\Resources\TaxGroupResource;

class TaxGroupController extends Controller
{
    public function __construct(
        private readonly TaxGroupRepositoryInterface $repository,
        private readonly CreateTaxGroupServiceInterface $createService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID', 1);
        return response()->json($this->repository->findAll($tenantId));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id'   => ['required', 'integer'],
            'name'        => ['required', 'string', 'max:255'],
            'code'        => ['required', 'string', 'max:50'],
            'is_active'   => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string'],
        ]);
        $data = new TaxGroupData(
            tenantId: $validated['tenant_id'],
            name: $validated['name'],
            code: $validated['code'],
            isActive: $validated['is_active'] ?? true,
            description: $validated['description'] ?? null,
        );
        return response()->json(new TaxGroupResource($this->createService->execute($data)), 201);
    }

    public function show(int $id): JsonResponse
    {
        $group = $this->repository->findById($id);
        if (!$group) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json(new TaxGroupResource($group));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $group = $this->repository->findById($id);
        if (!$group) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $data = $request->validate([
            'name'        => ['sometimes', 'string'],
            'code'        => ['sometimes', 'string'],
            'is_active'   => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string'],
        ]);
        return response()->json(new TaxGroupResource($this->repository->update($group, $data)));
    }

    public function destroy(int $id): JsonResponse
    {
        $group = $this->repository->findById($id);
        if (!$group) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $this->repository->delete($group);
        return response()->json(null, 204);
    }
}
