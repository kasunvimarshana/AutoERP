<?php

declare(strict_types=1);

namespace Modules\Product\Interfaces\Http\Controllers;

use App\Shared\Abstractions\BaseController;
use DomainException;
use Illuminate\Http\JsonResponse;
use Modules\Product\Application\Commands\CreateProductCommand;
use Modules\Product\Application\Commands\DeleteProductCommand;
use Modules\Product\Application\Commands\UpdateProductCommand;
use Modules\Product\Application\Services\ProductService;
use Modules\Product\Interfaces\Http\Requests\CreateProductRequest;
use Modules\Product\Interfaces\Http\Requests\UpdateProductRequest;
use Modules\Product\Interfaces\Http\Resources\ProductResource;

class ProductController extends BaseController
{
    public function __construct(
        private readonly ProductService $productService,
    ) {}

    public function index(): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $page = (int) request('page', 1);
        $perPage = (int) request('per_page', 25);

        $result = $this->productService->listProducts($tenantId, $page, $perPage);

        return $this->success(
            data: array_map(
                fn ($product) => (new ProductResource($product))->resolve(),
                $result['items']
            ),
            message: 'Products retrieved successfully',
            meta: [
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
            ],
        );
    }

    public function store(CreateProductRequest $request): JsonResponse
    {
        try {
            $product = $this->productService->createProduct(new CreateProductCommand(
                tenantId: (int) $request->validated('tenant_id'),
                sku: $request->validated('sku'),
                name: $request->validated('name'),
                description: $request->validated('description'),
                type: $request->validated('type'),
                uom: $request->validated('uom'),
                buyingUom: $request->validated('buying_uom'),
                sellingUom: $request->validated('selling_uom'),
                costingMethod: $request->validated('costing_method'),
                costPrice: (string) $request->validated('cost_price'),
                salePrice: (string) $request->validated('sale_price'),
                barcode: $request->validated('barcode'),
                status: $request->validated('status', 'active'),
            ));

            return $this->success(
                data: (new ProductResource($product))->resolve(),
                message: 'Product created successfully',
                status: 201,
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $product = $this->productService->findProductById($id, $tenantId);

        if ($product === null) {
            return $this->error('Product not found', status: 404);
        }

        return $this->success(
            data: (new ProductResource($product))->resolve(),
            message: 'Product retrieved successfully',
        );
    }

    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        try {
            $tenantId = (int) $request->query('tenant_id', '0');

            $product = $this->productService->updateProduct(new UpdateProductCommand(
                id: $id,
                tenantId: $tenantId,
                name: $request->validated('name'),
                description: $request->validated('description'),
                uom: $request->validated('uom'),
                buyingUom: $request->validated('buying_uom'),
                sellingUom: $request->validated('selling_uom'),
                costingMethod: $request->validated('costing_method'),
                costPrice: (string) $request->validated('cost_price'),
                salePrice: (string) $request->validated('sale_price'),
                barcode: $request->validated('barcode'),
                status: $request->validated('status'),
            ));

            return $this->success(
                data: (new ProductResource($product))->resolve(),
                message: 'Product updated successfully',
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 404);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $this->productService->deleteProduct(new DeleteProductCommand($id, $tenantId));

            return $this->success(message: 'Product deleted successfully');
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 404);
        }
    }
}
