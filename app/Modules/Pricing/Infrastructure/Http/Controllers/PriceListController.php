<?php
namespace Modules\Pricing\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Pricing\Application\Contracts\CreatePriceListServiceInterface;
use Modules\Pricing\Application\Contracts\DeletePriceListServiceInterface;
use Modules\Pricing\Application\Contracts\UpdatePriceListServiceInterface;
use Modules\Pricing\Application\DTOs\PriceListData;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListRepositoryInterface;
use Modules\Pricing\Infrastructure\Http\Resources\PriceListResource;

class PriceListController extends Controller
{
    public function __construct(
        private readonly PriceListRepositoryInterface $repository,
        private readonly CreatePriceListServiceInterface $createService,
        private readonly UpdatePriceListServiceInterface $updateService,
        private readonly DeletePriceListServiceInterface $deleteService,
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
            'currency'    => ['sometimes', 'string', 'size:3'],
            'is_default'  => ['sometimes', 'boolean'],
            'is_active'   => ['sometimes', 'boolean'],
            'valid_from'  => ['nullable', 'date'],
            'valid_to'    => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
        ]);
        $data = new PriceListData(
            tenantId: $validated['tenant_id'],
            name: $validated['name'],
            code: $validated['code'],
            currency: $validated['currency'] ?? 'USD',
            isDefault: $validated['is_default'] ?? false,
            isActive: $validated['is_active'] ?? true,
            validFrom: $validated['valid_from'] ?? null,
            validTo: $validated['valid_to'] ?? null,
            description: $validated['description'] ?? null,
        );
        return response()->json(new PriceListResource($this->createService->execute($data)), 201);
    }

    public function show(int $id): JsonResponse
    {
        $pl = $this->repository->findById($id);
        if (!$pl) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json(new PriceListResource($pl));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $pl = $this->repository->findById($id);
        if (!$pl) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $data = $request->validate([
            'name'        => ['sometimes', 'string'],
            'code'        => ['sometimes', 'string'],
            'currency'    => ['sometimes', 'string', 'size:3'],
            'is_default'  => ['sometimes', 'boolean'],
            'is_active'   => ['sometimes', 'boolean'],
            'valid_from'  => ['nullable', 'date'],
            'valid_to'    => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
        ]);
        return response()->json(new PriceListResource($this->updateService->execute($pl, $data)));
    }

    public function destroy(int $id): JsonResponse
    {
        $pl = $this->repository->findById($id);
        if (!$pl) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $this->deleteService->execute($pl);
        return response()->json(null, 204);
    }
}
