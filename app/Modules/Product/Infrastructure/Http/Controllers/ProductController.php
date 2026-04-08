<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Product\Application\Contracts\BulkUploadProductImagesServiceInterface;
use Modules\Product\Application\Contracts\CreateProductServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductServiceInterface;
use Modules\Product\Application\Contracts\FindProductServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductServiceInterface;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Infrastructure\Http\Requests\StoreProductRequest;
use Modules\Product\Infrastructure\Http\Requests\UpdateProductRequest;
use Modules\Product\Infrastructure\Http\Resources\ProductCollection;
use Modules\Product\Infrastructure\Http\Resources\ProductResource;
use OpenApi\Attributes as OA;

class ProductController extends AuthorizedController
{
    public function __construct(
        protected FindProductServiceInterface $findService,
        protected CreateProductServiceInterface $createService,
        protected UpdateProductServiceInterface $updateService,
        protected DeleteProductServiceInterface $deleteService,
        protected BulkUploadProductImagesServiceInterface $bulkUploadService
    ) {}

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

        $products = $this->findService->list($filters, $perPage, $page, $sort);

        return new ProductCollection($products);
    }

    #[OA\Post(
        path: '/api/products',
        summary: 'Create product',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
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
                        new OA\Property(property: 'type',        type: 'string',   nullable: true, enum: ['physical', 'service', 'digital', 'combo', 'variable'], example: 'physical'),
                        new OA\Property(
                            property: 'units_of_measure',
                            type: 'array',
                            nullable: true,
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'unit',              type: 'string', example: 'kg'),
                                    new OA\Property(property: 'type',              type: 'string', enum: ['buying', 'selling', 'inventory'], example: 'buying'),
                                    new OA\Property(property: 'conversion_factor', type: 'number', format: 'float', example: 1.0),
                                ],
                            ),
                        ),
                        new OA\Property(property: 'attributes',  type: 'object', nullable: true),
                        new OA\Property(property: 'metadata',    type: 'object', nullable: true),
                        new OA\Property(
                            property: 'product_attributes',
                            type: 'array',
                            nullable: true,
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'code',           type: 'string', maxLength: 50,  example: 'color'),
                                    new OA\Property(property: 'name',           type: 'string', maxLength: 100, example: 'Color'),
                                    new OA\Property(property: 'allowed_values', type: 'array',  nullable: true,
                                        items: new OA\Items(type: 'string', example: 'Red')),
                                ],
                            ),
                        ),
                        new OA\Property(
                            property: 'images',
                            type: 'array',
                            nullable: true,
                            items: new OA\Items(type: 'string', format: 'binary'),
                            description: 'Optional product images (JPEG/PNG/GIF/WebP, max 10 MB each)',
                        ),
                        new OA\Property(property: 'primary_image', type: 'integer', nullable: true, example: 0,
                            description: 'Zero-based index of the primary image within the images array'),
                    ],
                ),
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

        $validated = $request->validated();

        // Extract image files before passing to product service.
        $imageFiles   = $validated['images'] ?? [];
        $primaryIndex = isset($validated['primary_image']) ? (int) $validated['primary_image'] : 0;
        unset($validated['images'], $validated['primary_image']);

        $product = $this->createService->execute($validated);

        // Upload any images provided together with the product creation request.
        if (! empty($imageFiles) && $product->getId() !== null) {
            $this->bulkUploadService->execute([
                'product_id'       => $product->getId(),
                'files'            => $imageFiles,
                'sort_order_start' => 0,
                'is_primary_index' => $primaryIndex,
                'metadata'         => null,
            ]);
            // Reload the product so the resource includes the newly uploaded images.
            $product = $this->findService->find($product->getId()) ?? $product;
        }

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
        $product = $this->findService->find($id);
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
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'name',        type: 'string',  maxLength: 255),
                        new OA\Property(property: 'description', type: 'string',  nullable: true),
                        new OA\Property(property: 'price',       type: 'number',  format: 'float'),
                        new OA\Property(property: 'currency',    type: 'string',  nullable: true, maxLength: 3),
                        new OA\Property(property: 'category',    type: 'string',  nullable: true),
                        new OA\Property(property: 'status',      type: 'string',  nullable: true, enum: ['active', 'inactive', 'draft']),
                        new OA\Property(property: 'type',        type: 'string',  nullable: true, enum: ['physical', 'service', 'digital', 'combo', 'variable']),
                        new OA\Property(
                            property: 'units_of_measure',
                            type: 'array',
                            nullable: true,
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'unit',              type: 'string'),
                                    new OA\Property(property: 'type',              type: 'string', enum: ['buying', 'selling', 'inventory']),
                                    new OA\Property(property: 'conversion_factor', type: 'number', format: 'float'),
                                ],
                            ),
                        ),
                        new OA\Property(property: 'attributes',  type: 'object', nullable: true),
                        new OA\Property(property: 'metadata',    type: 'object', nullable: true),
                        new OA\Property(
                            property: 'product_attributes',
                            type: 'array',
                            nullable: true,
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'code',           type: 'string', maxLength: 50),
                                    new OA\Property(property: 'name',           type: 'string', maxLength: 100),
                                    new OA\Property(property: 'allowed_values', type: 'array',  nullable: true,
                                        items: new OA\Items(type: 'string')),
                                ],
                            ),
                        ),
                        new OA\Property(
                            property: 'images',
                            type: 'array',
                            nullable: true,
                            items: new OA\Items(type: 'string', format: 'binary'),
                            description: 'Optional additional images (JPEG/PNG/GIF/WebP, max 10 MB each)',
                        ),
                        new OA\Property(property: 'primary_image', type: 'integer', nullable: true, example: 0),
                    ],
                ),
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
        $product = $this->findService->find($id);
        if (! $product) {
            abort(404);
        }
        $this->authorize('update', $product);

        $validated = $request->validated();

        // Extract image files before passing to product service.
        $imageFiles   = $validated['images'] ?? [];
        $primaryIndex = isset($validated['primary_image']) ? (int) $validated['primary_image'] : 0;
        unset($validated['images'], $validated['primary_image']);

        // Always wire in the system-managed fields.
        $validated['id']        = $id;
        $validated['tenant_id'] = $product->getTenantId();
        $validated['sku']       = $product->getSku()->value();

        // Fill name / price / currency from the existing product when omitted from the
        // request so that partial updates do not break UpdateProductService's type
        // contracts (name: string, price: float).
        if (! array_key_exists('name', $validated)) {
            $validated['name'] = $product->getName();
        }
        if (! array_key_exists('price', $validated)) {
            $validated['price'] = $product->getPrice()->getAmount();
        }
        if (! array_key_exists('currency', $validated)) {
            $validated['currency'] = $product->getPrice()->getCurrency();
        }

        $updated = $this->updateService->execute($validated);

        // Append any new images supplied together with the update request.
        if (! empty($imageFiles) && $updated->getId() !== null) {
            $this->bulkUploadService->execute([
                'product_id'       => $updated->getId(),
                'files'            => $imageFiles,
                'sort_order_start' => 0,
                'is_primary_index' => $primaryIndex,
                'metadata'         => null,
            ]);
            $updated = $this->findService->find($updated->getId()) ?? $updated;
        }

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
        $product = $this->findService->find($id);
        if (! $product) {
            abort(404);
        }
        $this->authorize('delete', $product);
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Product deleted successfully']);
    }
}
