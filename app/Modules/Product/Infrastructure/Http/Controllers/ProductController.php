<?php
namespace Modules\Product\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Product\Application\Contracts\CreateProductServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductServiceInterface;
use Modules\Product\Application\DTOs\ProductData;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;
use Modules\Product\Infrastructure\Http\Resources\ProductResource;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
        private readonly CreateProductServiceInterface $createService,
        private readonly UpdateProductServiceInterface $updateService,
        private readonly DeleteProductServiceInterface $deleteService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->query('tenant_id', 0);
        $filters  = $request->only(['status', 'type', 'category_id']);
        $perPage  = (int) $request->query('per_page', 15);
        $products = $this->repository->findAll($tenantId, $filters, $perPage);
        return response()->json($products);
    }

    public function store(Request $request): JsonResponse
    {
        $data = new ProductData(
            tenantId:       (int) $request->input('tenant_id'),
            sku:            $request->input('sku'),
            name:           $request->input('name'),
            type:           $request->input('type'),
            status:         $request->input('status', 'active'),
            categoryId:     $request->input('category_id') !== null ? (int) $request->input('category_id') : null,
            description:    $request->input('description'),
            barcode:        $request->input('barcode'),
            basePrice:      $request->input('base_price') !== null ? (float) $request->input('base_price') : null,
            costPrice:      $request->input('cost_price') !== null ? (float) $request->input('cost_price') : null,
            baseUomId:      $request->input('base_uom_id') !== null ? (int) $request->input('base_uom_id') : null,
            trackInventory: (bool) $request->input('track_inventory', true),
            trackBatch:     (bool) $request->input('track_batch', false),
            trackSerial:    (bool) $request->input('track_serial', false),
            trackLot:       (bool) $request->input('track_lot', false),
            attributes:     $request->input('attributes'),
        );
        $product = $this->createService->execute($data);
        return response()->json(new ProductResource($product), 201);
    }

    public function show(int $id): JsonResponse
    {
        $product = $this->repository->findById($id);
        if (!$product) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json(new ProductResource($product));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $product = $this->repository->findById($id);
        if (!$product) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $data = new ProductData(
            tenantId:       $product->tenantId,
            sku:            $request->input('sku', $product->sku),
            name:           $request->input('name', $product->name),
            type:           $request->input('type', $product->type),
            status:         $request->input('status', $product->status),
            categoryId:     $request->has('category_id') ? ($request->input('category_id') !== null ? (int) $request->input('category_id') : null) : $product->categoryId,
            description:    $request->input('description', $product->description),
            barcode:        $request->input('barcode', $product->barcode),
            basePrice:      $request->input('base_price') !== null ? (float) $request->input('base_price') : $product->basePrice,
            costPrice:      $request->input('cost_price') !== null ? (float) $request->input('cost_price') : $product->costPrice,
            baseUomId:      $request->has('base_uom_id') ? ($request->input('base_uom_id') !== null ? (int) $request->input('base_uom_id') : null) : $product->baseUomId,
            trackInventory: $request->input('track_inventory', $product->trackInventory),
            trackBatch:     $request->input('track_batch', $product->trackBatch),
            trackSerial:    $request->input('track_serial', $product->trackSerial),
            trackLot:       $request->input('track_lot', $product->trackLot),
            attributes:     $request->input('attributes', $product->attributes),
        );
        $updated = $this->updateService->execute($product, $data);
        return response()->json(new ProductResource($updated));
    }

    public function destroy(int $id): JsonResponse
    {
        $product = $this->repository->findById($id);
        if (!$product) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $this->deleteService->execute($product);
        return response()->json(null, 204);
    }
}
