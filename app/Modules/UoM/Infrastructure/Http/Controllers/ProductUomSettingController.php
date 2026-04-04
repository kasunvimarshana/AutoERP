<?php
namespace Modules\UoM\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\UoM\Application\Contracts\CreateProductUomSettingServiceInterface;
use Modules\UoM\Application\Contracts\UpdateProductUomSettingServiceInterface;
use Modules\UoM\Application\DTOs\ProductUomSettingData;
use Modules\UoM\Domain\RepositoryInterfaces\ProductUomSettingRepositoryInterface;
use Modules\UoM\Infrastructure\Http\Resources\ProductUomSettingResource;

class ProductUomSettingController extends Controller
{
    public function __construct(
        private readonly ProductUomSettingRepositoryInterface $repository,
        private readonly CreateProductUomSettingServiceInterface $createService,
        private readonly UpdateProductUomSettingServiceInterface $updateService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $productId = $request->query('product_id');
        if ($productId) {
            $setting = $this->repository->findByProduct((int) $productId);
            return response()->json($setting ? new ProductUomSettingResource($setting) : null);
        }
        return response()->json([]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id'       => 'required|integer',
            'base_uom_id'      => 'required|integer',
            'purchase_uom_id'  => 'nullable|integer',
            'sales_uom_id'     => 'nullable|integer',
            'inventory_uom_id' => 'nullable|integer',
            'purchase_factor'  => 'numeric',
            'sales_factor'     => 'numeric',
            'inventory_factor' => 'numeric',
        ]);

        $data = new ProductUomSettingData(
            productId: $validated['product_id'],
            baseUomId: $validated['base_uom_id'],
            purchaseUomId: $validated['purchase_uom_id'] ?? null,
            salesUomId: $validated['sales_uom_id'] ?? null,
            inventoryUomId: $validated['inventory_uom_id'] ?? null,
            purchaseFactor: $validated['purchase_factor'] ?? 1.0,
            salesFactor: $validated['sales_factor'] ?? 1.0,
            inventoryFactor: $validated['inventory_factor'] ?? 1.0,
        );

        $setting = $this->createService->execute($data);
        return response()->json(new ProductUomSettingResource($setting), 201);
    }

    public function show(int $id): JsonResponse
    {
        $setting = $this->repository->findById($id);
        if (!$setting) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json(new ProductUomSettingResource($setting));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'product_id'       => 'required|integer',
            'base_uom_id'      => 'required|integer',
            'purchase_uom_id'  => 'nullable|integer',
            'sales_uom_id'     => 'nullable|integer',
            'inventory_uom_id' => 'nullable|integer',
            'purchase_factor'  => 'numeric',
            'sales_factor'     => 'numeric',
            'inventory_factor' => 'numeric',
        ]);

        $data = new ProductUomSettingData(
            productId: $validated['product_id'],
            baseUomId: $validated['base_uom_id'],
            purchaseUomId: $validated['purchase_uom_id'] ?? null,
            salesUomId: $validated['sales_uom_id'] ?? null,
            inventoryUomId: $validated['inventory_uom_id'] ?? null,
            purchaseFactor: $validated['purchase_factor'] ?? 1.0,
            salesFactor: $validated['sales_factor'] ?? 1.0,
            inventoryFactor: $validated['inventory_factor'] ?? 1.0,
        );

        $setting = $this->updateService->execute($id, $data);
        return response()->json(new ProductUomSettingResource($setting));
    }
}
