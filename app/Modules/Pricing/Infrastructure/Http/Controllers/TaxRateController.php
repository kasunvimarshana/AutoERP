<?php
namespace Modules\Pricing\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Pricing\Application\Contracts\CreateTaxRateServiceInterface;
use Modules\Pricing\Application\DTOs\TaxRateData;
use Modules\Pricing\Domain\RepositoryInterfaces\TaxRateRepositoryInterface;
use Modules\Pricing\Infrastructure\Http\Resources\TaxRateResource;

class TaxRateController extends Controller
{
    public function __construct(
        private readonly TaxRateRepositoryInterface $repository,
        private readonly CreateTaxRateServiceInterface $createService,
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
            'rate'        => ['required', 'numeric', 'min:0'],
            'type'        => ['sometimes', 'string', 'in:percentage,fixed'],
            'is_active'   => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string'],
            'region'      => ['nullable', 'string', 'max:100'],
            'tax_class'   => ['nullable', 'string', 'max:50'],
        ]);
        $data = new TaxRateData(
            tenantId: $validated['tenant_id'],
            name: $validated['name'],
            code: $validated['code'],
            rate: (float) $validated['rate'],
            type: $validated['type'] ?? 'percentage',
            isActive: $validated['is_active'] ?? true,
            description: $validated['description'] ?? null,
            region: $validated['region'] ?? null,
            taxClass: $validated['tax_class'] ?? null,
        );
        return response()->json(new TaxRateResource($this->createService->execute($data)), 201);
    }

    public function show(int $id): JsonResponse
    {
        $rate = $this->repository->findById($id);
        if (!$rate) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json(new TaxRateResource($rate));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $rate = $this->repository->findById($id);
        if (!$rate) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $data = $request->validate([
            'name'        => ['sometimes', 'string'],
            'code'        => ['sometimes', 'string'],
            'rate'        => ['sometimes', 'numeric', 'min:0'],
            'type'        => ['sometimes', 'string', 'in:percentage,fixed'],
            'is_active'   => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string'],
            'region'      => ['nullable', 'string'],
            'tax_class'   => ['nullable', 'string'],
        ]);
        return response()->json(new TaxRateResource($this->repository->update($rate, $data)));
    }

    public function destroy(int $id): JsonResponse
    {
        $rate = $this->repository->findById($id);
        if (!$rate) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $this->repository->delete($rate);
        return response()->json(null, 204);
    }
}
