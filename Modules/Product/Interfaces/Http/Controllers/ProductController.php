<?php

declare(strict_types=1);

namespace Modules\Product\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Product\Application\Commands\CreateProductCommand;
use Modules\Product\Application\Commands\UpdateProductCommand;
use Modules\Product\Application\Handlers\CreateProductHandler;
use Modules\Product\Application\Handlers\UpdateProductHandler;
use Modules\Product\Application\Services\BarcodeService;
use Modules\Product\Domain\Contracts\ProductRepositoryInterface;
use Modules\Product\Interfaces\Http\Requests\CreateProductRequest;
use Modules\Product\Interfaces\Http\Resources\ProductResource;

class ProductController extends Controller
{
    public function __construct(
        private readonly CreateProductHandler      $createHandler,
        private readonly UpdateProductHandler      $updateHandler,
        private readonly ProductRepositoryInterface $products,
        private readonly BarcodeService            $barcodeService,
    ) {}

    /**
     * GET /api/v1/products
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $page     = (int) $request->query('page', 1);
        $perPage  = min((int) $request->query('per_page', 25), 100);

        $items = $this->products->findAll($tenantId, $page, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Products retrieved successfully.',
            'data'    => array_map(fn ($p) => [
                'id'            => $p->getId(),
                'name'          => $p->getName(),
                'sku'           => $p->getSku()->getValue(),
                'type'          => $p->getType()->value,
                'cost_price'    => $p->getCostPrice(),
                'selling_price' => $p->getSellingPrice(),
                'margin'        => $p->calculateMargin(),
                'is_active'     => $p->isActive(),
            ], $items),
            'errors'  => null,
        ]);
    }

    /**
     * GET /api/v1/products/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $product  = $this->products->findById($id, $tenantId);

        if ($product === null) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Product retrieved successfully.',
            'data'    => [
                'id'            => $product->getId(),
                'name'          => $product->getName(),
                'sku'           => $product->getSku()->getValue(),
                'type'          => $product->getType()->value,
                'cost_price'    => $product->getCostPrice(),
                'selling_price' => $product->getSellingPrice(),
                'reorder_point' => $product->getReorderPoint(),
                'margin'        => $product->calculateMargin(),
                'is_active'     => $product->isActive(),
                'description'   => $product->getDescription(),
            ],
            'errors'  => null,
        ]);
    }

    /**
     * POST /api/v1/products
     */
    public function store(CreateProductRequest $request): JsonResponse
    {
        try {
            $product = $this->createHandler->handle(new CreateProductCommand(
                tenantId: (int) $request->attributes->get('tenant_id'),
                name: $request->input('name'),
                sku: $request->input('sku'),
                type: $request->input('type', 'single'),
                costPrice: $request->input('cost_price', '0'),
                sellingPrice: $request->input('selling_price', '0'),
                reorderPoint: $request->input('reorder_point', '0'),
                categoryId: $request->input('category_id') ? (int) $request->input('category_id') : null,
                brandId: $request->input('brand_id') ? (int) $request->input('brand_id') : null,
                unitId: $request->input('unit_id') ? (int) $request->input('unit_id') : null,
                description: $request->input('description'),
                barcode: $request->input('barcode'),
                isActive: (bool) $request->input('is_active', true),
            ));

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully.',
                'data'    => [
                    'id'   => $product->getId(),
                    'name' => $product->getName(),
                    'sku'  => $product->getSku()->getValue(),
                ],
                'errors'  => null,
            ], 201);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => null,
                'errors'  => ['sku' => [$e->getMessage()]],
            ], 422);
        }
    }

    /**
     * PUT /api/v1/products/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'type'          => 'required|string',
            'cost_price'    => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'reorder_point' => 'nullable|numeric|min:0',
            'category_id'   => 'nullable|integer|exists:categories,id',
            'brand_id'      => 'nullable|integer|exists:brands,id',
            'unit_id'       => 'nullable|integer|exists:units,id',
            'description'   => 'nullable|string',
            'is_active'     => 'nullable|boolean',
        ]);

        try {
            $product = $this->updateHandler->handle(new UpdateProductCommand(
                id: $id,
                tenantId: (int) $request->attributes->get('tenant_id'),
                name: $validated['name'],
                type: $validated['type'],
                costPrice: (string) $validated['cost_price'],
                sellingPrice: (string) $validated['selling_price'],
                reorderPoint: (string) ($validated['reorder_point'] ?? '0'),
                categoryId: isset($validated['category_id']) ? (int) $validated['category_id'] : null,
                brandId: isset($validated['brand_id']) ? (int) $validated['brand_id'] : null,
                unitId: isset($validated['unit_id']) ? (int) $validated['unit_id'] : null,
                description: $validated['description'] ?? null,
                isActive: (bool) ($validated['is_active'] ?? true),
            ));
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully.',
            'data'    => [
                'id'   => $product->getId(),
                'name' => $product->getName(),
                'sku'  => $product->getSku()->getValue(),
            ],
            'errors'  => null,
        ]);
    }

    /**
     * DELETE /api/v1/products/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $this->products->delete($id, $tenantId);

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully.',
            'data'    => null,
            'errors'  => null,
        ]);
    }

    /**
     * GET /api/v1/products/{id}/barcode
     *
     * Generates a barcode for the given product's SKU.
     * Based on the BarcodeController in the PHP_POS reference.
     */
    public function barcode(Request $request, int $id): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $product  = $this->products->findById($id, $tenantId);

        if ($product === null) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        $barcodeType = strtoupper((string) $request->query('type', 'CODE128'));

        try {
            $barcode = $this->barcodeService->generate($product->getSku()->getValue(), $barcodeType);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => null,
                'errors'  => ['type' => [$e->getMessage()]],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Barcode generated successfully.',
            'data'    => [
                'product_id' => $product->getId(),
                'sku'        => $product->getSku()->getValue(),
                'barcode'    => $barcode,
            ],
            'errors'  => null,
        ]);
    }
}
