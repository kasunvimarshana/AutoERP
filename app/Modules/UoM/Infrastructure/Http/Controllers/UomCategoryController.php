<?php
namespace Modules\UoM\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\UoM\Application\Contracts\CreateUomCategoryServiceInterface;
use Modules\UoM\Application\Contracts\DeleteUomCategoryServiceInterface;
use Modules\UoM\Application\Contracts\UpdateUomCategoryServiceInterface;
use Modules\UoM\Application\DTOs\UomCategoryData;
use Modules\UoM\Domain\RepositoryInterfaces\UomCategoryRepositoryInterface;
use Modules\UoM\Infrastructure\Http\Resources\UomCategoryResource;

class UomCategoryController extends Controller
{
    public function __construct(
        private readonly UomCategoryRepositoryInterface $repository,
        private readonly CreateUomCategoryServiceInterface $createService,
        private readonly UpdateUomCategoryServiceInterface $updateService,
        private readonly DeleteUomCategoryServiceInterface $deleteService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->query('tenant_id', 0);
        $filters  = $request->only(['name', 'measure_type', 'is_active']);
        $perPage  = (int) $request->query('per_page', 15);

        $paginator = $this->repository->findAll($tenantId, $filters, $perPage);
        return response()->json($paginator);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id'    => 'required|integer',
            'name'         => 'required|string|max:255',
            'measure_type' => 'required|string|max:50',
            'is_active'    => 'boolean',
            'description'  => 'nullable|string',
        ]);

        $data = new UomCategoryData(
            tenantId: $validated['tenant_id'],
            name: $validated['name'],
            measureType: $validated['measure_type'],
            isActive: $validated['is_active'] ?? true,
            description: $validated['description'] ?? null,
        );

        $category = $this->createService->execute($data);
        return response()->json(new UomCategoryResource($category), 201);
    }

    public function show(int $id): JsonResponse
    {
        $category = $this->repository->findById($id);
        if (!$category) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json(new UomCategoryResource($category));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id'    => 'required|integer',
            'name'         => 'required|string|max:255',
            'measure_type' => 'required|string|max:50',
            'is_active'    => 'boolean',
            'description'  => 'nullable|string',
        ]);

        $data = new UomCategoryData(
            tenantId: $validated['tenant_id'],
            name: $validated['name'],
            measureType: $validated['measure_type'],
            isActive: $validated['is_active'] ?? true,
            description: $validated['description'] ?? null,
        );

        $category = $this->updateService->execute($id, $data);
        return response()->json(new UomCategoryResource($category));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteService->execute($id);
        return response()->json(null, 204);
    }
}
