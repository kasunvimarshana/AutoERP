<?php
namespace Modules\UoM\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\UoM\Application\Contracts\CreateUnitOfMeasureServiceInterface;
use Modules\UoM\Application\Contracts\DeleteUnitOfMeasureServiceInterface;
use Modules\UoM\Application\Contracts\UpdateUnitOfMeasureServiceInterface;
use Modules\UoM\Application\DTOs\UnitOfMeasureData;
use Modules\UoM\Domain\RepositoryInterfaces\UnitOfMeasureRepositoryInterface;
use Modules\UoM\Infrastructure\Http\Resources\UnitOfMeasureResource;

class UnitOfMeasureController extends Controller
{
    public function __construct(
        private readonly UnitOfMeasureRepositoryInterface $repository,
        private readonly CreateUnitOfMeasureServiceInterface $createService,
        private readonly UpdateUnitOfMeasureServiceInterface $updateService,
        private readonly DeleteUnitOfMeasureServiceInterface $deleteService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->query('tenant_id', 0);
        $filters  = $request->only(['name', 'symbol', 'category_id', 'is_active', 'is_base']);
        $perPage  = (int) $request->query('per_page', 15);

        $paginator = $this->repository->findAll($tenantId, $filters, $perPage);
        return response()->json($paginator);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id'         => 'required|integer',
            'category_id'       => 'required|integer',
            'name'              => 'required|string|max:255',
            'symbol'            => 'required|string|max:20',
            'conversion_factor' => 'numeric',
            'is_base'           => 'boolean',
            'is_active'         => 'boolean',
        ]);

        $data = new UnitOfMeasureData(
            tenantId: $validated['tenant_id'],
            categoryId: $validated['category_id'],
            name: $validated['name'],
            symbol: $validated['symbol'],
            conversionFactor: $validated['conversion_factor'] ?? 1.0,
            isBase: $validated['is_base'] ?? false,
            isActive: $validated['is_active'] ?? true,
        );

        $uom = $this->createService->execute($data);
        return response()->json(new UnitOfMeasureResource($uom), 201);
    }

    public function show(int $id): JsonResponse
    {
        $uom = $this->repository->findById($id);
        if (!$uom) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json(new UnitOfMeasureResource($uom));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id'         => 'required|integer',
            'category_id'       => 'required|integer',
            'name'              => 'required|string|max:255',
            'symbol'            => 'required|string|max:20',
            'conversion_factor' => 'numeric',
            'is_base'           => 'boolean',
            'is_active'         => 'boolean',
        ]);

        $data = new UnitOfMeasureData(
            tenantId: $validated['tenant_id'],
            categoryId: $validated['category_id'],
            name: $validated['name'],
            symbol: $validated['symbol'],
            conversionFactor: $validated['conversion_factor'] ?? 1.0,
            isBase: $validated['is_base'] ?? false,
            isActive: $validated['is_active'] ?? true,
        );

        $uom = $this->updateService->execute($id, $data);
        return response()->json(new UnitOfMeasureResource($uom));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteService->execute($id);
        return response()->json(null, 204);
    }

    public function byCategory(int $categoryId): JsonResponse
    {
        $uoms = $this->repository->findByCategory($categoryId);
        return response()->json(array_map(fn($u) => new UnitOfMeasureResource($u), $uoms));
    }
}
