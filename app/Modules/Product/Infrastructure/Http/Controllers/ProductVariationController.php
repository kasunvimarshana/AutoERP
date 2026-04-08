<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Product\Application\Contracts\CreateProductServiceInterface;
use Modules\Product\Application\Contracts\CreateProductVariationServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductVariationServiceInterface;
use Modules\Product\Application\Contracts\FindProductVariationsServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductVariationServiceInterface;
use Modules\Product\Application\DTOs\ProductVariationData;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Infrastructure\Http\Requests\StoreProductVariationRequest;
use Modules\Product\Infrastructure\Http\Requests\UpdateProductVariationRequest;
use Modules\Product\Infrastructure\Http\Resources\ProductVariationResource;
use OpenApi\Attributes as OA;

class ProductVariationController extends AuthorizedController
{
    public function __construct(
        protected CreateProductVariationServiceInterface $createService,
        protected UpdateProductVariationServiceInterface $updateService,
        protected DeleteProductVariationServiceInterface $deleteService,
        protected CreateProductServiceInterface $productService,
        protected FindProductVariationsServiceInterface $variationService,
    ) {}

    #[OA\Get(
        path: '/api/products/{productId}/variations',
        summary: 'List product variations',
        tags: ['Product Variations'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'productId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of product variations',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'object'))),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function index(int $productId): JsonResponse
    {
        $this->authorize('viewAny', Product::class);
        $variations = $this->variationService->findByProduct($productId);

        return response()->json(['data' => ProductVariationResource::collection($variations)]);
    }

    #[OA\Post(
        path: '/api/products/{productId}/variations',
        summary: 'Create product variation',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['sku', 'name', 'price'],
                properties: [
                    new OA\Property(property: 'sku',              type: 'string',  maxLength: 100, example: 'PROD-001-RED-M'),
                    new OA\Property(property: 'name',             type: 'string',  maxLength: 255, example: 'Widget Pro (Red, M)'),
                    new OA\Property(property: 'price',            type: 'number',  format: 'float', example: 29.99),
                    new OA\Property(property: 'currency',         type: 'string',  nullable: true, maxLength: 3, example: 'USD'),
                    new OA\Property(property: 'attribute_values', type: 'object',  nullable: true),
                    new OA\Property(property: 'status',           type: 'string',  nullable: true, enum: ['active', 'inactive'], example: 'active'),
                    new OA\Property(property: 'sort_order',       type: 'integer', nullable: true, example: 0),
                    new OA\Property(property: 'metadata',         type: 'object',  nullable: true),
                ],
            ),
        ),
        tags: ['Product Variations'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'productId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 201, description: 'Variation created', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function store(StoreProductVariationRequest $request, int $productId): JsonResponse
    {
        $this->authorize('create', Product::class);
        $product = $this->productService->find($productId);
        if (! $product) {
            abort(404);
        }

        $validated               = $request->validated();
        $validated['product_id'] = $productId;
        $validated['tenant_id']  = $product->getTenantId();
        $dto                     = ProductVariationData::fromArray($validated);

        $variation = $this->createService->execute($dto->toArray());

        return (new ProductVariationResource($variation))->response()->setStatusCode(201);
    }

    #[OA\Put(
        path: '/api/products/{productId}/variations/{variationId}',
        summary: 'Update product variation',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'price'],
                properties: [
                    new OA\Property(property: 'name',             type: 'string', maxLength: 255),
                    new OA\Property(property: 'price',            type: 'number', format: 'float'),
                    new OA\Property(property: 'currency',         type: 'string', nullable: true, maxLength: 3),
                    new OA\Property(property: 'attribute_values', type: 'object', nullable: true),
                    new OA\Property(property: 'status',           type: 'string', nullable: true, enum: ['active', 'inactive']),
                    new OA\Property(property: 'sort_order',       type: 'integer', nullable: true),
                    new OA\Property(property: 'metadata',         type: 'object',  nullable: true),
                ],
            ),
        ),
        tags: ['Product Variations'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'productId',   in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'variationId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Variation updated', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 404, description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function update(UpdateProductVariationRequest $request, int $productId, int $variationId): ProductVariationResource
    {
        $this->authorize('update', Product::class);
        $variation = $this->variationService->find($variationId);
        if (! $variation) {
            abort(404);
        }

        $validated        = $request->validated();
        $validated['id']  = $variationId;
        $validated['product_id'] = $productId;
        $validated['tenant_id']  = $variation->getTenantId();
        $validated['sku']        = $variation->getSku()->value();
        $dto = ProductVariationData::fromArray($validated);

        $updated = $this->updateService->execute($dto->toArray());

        return new ProductVariationResource($updated);
    }

    #[OA\Delete(
        path: '/api/products/{productId}/variations/{variationId}',
        summary: 'Delete product variation',
        tags: ['Product Variations'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'productId',   in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'variationId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Deleted',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 404, description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function destroy(int $productId, int $variationId): JsonResponse
    {
        $this->authorize('delete', Product::class);
        $variation = $this->variationService->find($variationId);
        if (! $variation) {
            abort(404);
        }

        $this->deleteService->execute(['id' => $variationId]);

        return response()->json(['message' => 'Product variation deleted successfully']);
    }
}
