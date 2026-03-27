<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\BaseController;
use Modules\Product\Application\Contracts\CreateProductServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductServiceInterface;
use Modules\Product\Application\DTOs\ProductData;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Infrastructure\Http\Requests\StoreProductRequest;
use Modules\Product\Infrastructure\Http\Requests\UpdateProductRequest;
use Modules\Product\Infrastructure\Http\Resources\ProductCollection;
use Modules\Product\Infrastructure\Http\Resources\ProductResource;
use OpenApi\Attributes as OA;

class ProductController extends BaseController
{
    public function __construct(
        CreateProductServiceInterface $createService,
        protected UpdateProductServiceInterface $updateService,
        protected DeleteProductServiceInterface $deleteService
    ) {
        parent::__construct($createService, ProductResource::class, ProductData::class);
    }

    #[OA\Get(
        path: '/api/products',
        summary: 'List products',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'name',      in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sku',       in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'category',  in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status',    in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['active', 'inactive', 'draft'])),
            new OA\Parameter(name: 'per_page',  in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'page',      in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'sort',      in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'name:asc')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of products',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data',  type: 'array', items: new OA\Items(ref: '#/components/schemas/ProductObject')),
                        new OA\Property(property: 'meta',  ref: '#/components/schemas/PaginationMeta'),
                        new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                    ],
                )),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function index(Request $request): ProductCollection
    {
        $this->authorize('viewAny', Product::class);
        $filters = $request->only(['name', 'sku', 'category', 'status']);
        $perPage = $request->integer('per_page', 15);
        $page = $request->integer('page', 1);
        $sort = $request->input('sort');

        $products = $this->service->list($filters, $perPage, $page, $sort);

        return new ProductCollection($products);
    }

    #[OA\Post(
        path: '/api/products',
        summary: 'Create product',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['tenant_id', 'sku', 'name', 'price'],
                properties: [
                    new OA\Property(property: 'tenant_id',   type: 'integer',  example: 1),
                    new OA\Property(property: 'sku',         type: 'string',   maxLength: 100, example: 'PROD-001'),
                    new OA\Property(property: 'name',        type: 'string',   maxLength: 255, example: 'Widget Pro'),
                    new OA\Property(property: 'description', type: 'string',   nullable: true, example: 'A high quality widget'),
                    new OA\Property(property: 'price',       type: 'number',   format: 'float', example: 29.99),
                    new OA\Property(property: 'currency',    type: 'string',   nullable: true, maxLength: 3, example: 'USD'),
                    new OA\Property(property: 'category',    type: 'string',   nullable: true, example: 'Widgets'),
                    new OA\Property(property: 'status',      type: 'string',   nullable: true, enum: ['active', 'inactive', 'draft'], example: 'active'),
                    new OA\Property(property: 'attributes',  type: 'object',   nullable: true),
                    new OA\Property(property: 'metadata',    type: 'object',   nullable: true),
                ],
            ),
        ),
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 201, description: 'Product created',
                content: new OA\JsonContent(ref: '#/components/schemas/ProductObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function store(StoreProductRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);
        $dto = ProductData::fromArray($request->validated());
        $product = $this->service->execute($dto->toArray());

        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/products/{id}',
        summary: 'Get product',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Product details',
                content: new OA\JsonContent(ref: '#/components/schemas/ProductObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function show(int $id): ProductResource
    {
        $product = $this->service->find($id);
        if (! $product) {
            abort(404);
        }
        $this->authorize('view', $product);

        return new ProductResource($product);
    }

    #[OA\Put(
        path: '/api/products/{id}',
        summary: 'Update product',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'price'],
                properties: [
                    new OA\Property(property: 'name',        type: 'string',  maxLength: 255),
                    new OA\Property(property: 'description', type: 'string',  nullable: true),
                    new OA\Property(property: 'price',       type: 'number',  format: 'float'),
                    new OA\Property(property: 'currency',    type: 'string',  nullable: true, maxLength: 3),
                    new OA\Property(property: 'category',    type: 'string',  nullable: true),
                    new OA\Property(property: 'status',      type: 'string',  nullable: true, enum: ['active', 'inactive', 'draft']),
                    new OA\Property(property: 'attributes',  type: 'object',  nullable: true),
                    new OA\Property(property: 'metadata',    type: 'object',  nullable: true),
                ],
            ),
        ),
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Updated product',
                content: new OA\JsonContent(ref: '#/components/schemas/ProductObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function update(UpdateProductRequest $request, int $id): ProductResource
    {
        $product = $this->service->find($id);
        if (! $product) {
            abort(404);
        }
        $this->authorize('update', $product);
        $validated = $request->validated();
        $validated['id'] = $id;
        $validated['tenant_id'] = $product->getTenantId();
        $validated['sku'] = $product->getSku()->value();
        $dto = ProductData::fromArray($validated);
        $updated = $this->updateService->execute($dto->toArray());

        return new ProductResource($updated);
    }

    #[OA\Delete(
        path: '/api/products/{id}',
        summary: 'Delete product',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Deleted',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function destroy(int $id): JsonResponse
    {
        $product = $this->service->find($id);
        if (! $product) {
            abort(404);
        }
        $this->authorize('delete', $product);
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Product deleted successfully']);
    }

    protected function getModelClass(): string
    {
        return Product::class;
    }
}
