<?php
namespace Modules\Product\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Product\Application\Contracts\CreateProductVariantServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductVariantServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductVariantServiceInterface;
use Modules\Product\Application\DTOs\ProductVariantData;
use Modules\Product\Domain\RepositoryInterfaces\ProductVariantRepositoryInterface;
use Modules\Product\Infrastructure\Http\Resources\ProductVariantResource;

class ProductVariantController extends Controller
{
    public function __construct(
        private readonly ProductVariantRepositoryInterface $repository,
        private readonly CreateProductVariantServiceInterface $createService,
        private readonly UpdateProductVariantServiceInterface $updateService,
        private readonly DeleteProductVariantServiceInterface $deleteService,
    ) {}

    public function index(int $productId): JsonResponse
    {
        $variants = $this->repository->findByProduct($productId);
        return response()->json($variants);
    }

    public function store(Request $request, int $productId): JsonResponse
    {
        $data = new ProductVariantData(
            productId:  $productId,
            sku:        $request->input('sku'),
            name:       $request->input('name'),
            basePrice:  $request->input('base_price') !== null ? (float) $request->input('base_price') : null,
            costPrice:  $request->input('cost_price') !== null ? (float) $request->input('cost_price') : null,
            barcode:    $request->input('barcode'),
            attributes: $request->input('attributes'),
            isActive:   (bool) $request->input('is_active', true),
        );
        $variant = $this->createService->execute($data);
        return response()->json(new ProductVariantResource($variant), 201);
    }

    public function show(int $id): JsonResponse
    {
        $variant = $this->repository->findById($id);
        if (!$variant) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json(new ProductVariantResource($variant));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $variant = $this->repository->findById($id);
        if (!$variant) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $data = new ProductVariantData(
            productId:  $variant->productId,
            sku:        $request->input('sku', $variant->sku),
            name:       $request->input('name', $variant->name),
            basePrice:  $request->input('base_price') !== null ? (float) $request->input('base_price') : $variant->basePrice,
            costPrice:  $request->input('cost_price') !== null ? (float) $request->input('cost_price') : $variant->costPrice,
            barcode:    $request->input('barcode', $variant->barcode),
            attributes: $request->input('attributes', $variant->attributes),
            isActive:   $request->input('is_active', $variant->isActive),
        );
        $updated = $this->updateService->execute($variant, $data);
        return response()->json(new ProductVariantResource($updated));
    }

    public function destroy(int $id): JsonResponse
    {
        $variant = $this->repository->findById($id);
        if (!$variant) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $this->deleteService->execute($variant);
        return response()->json(null, 204);
    }
}
