<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Product\Application\Contracts\BulkUploadProductImagesServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductImageServiceInterface;
use Modules\Product\Application\Contracts\FindProductImagesServiceInterface;
use Modules\Product\Application\Contracts\ImageStorageStrategyInterface;
use Modules\Product\Application\Contracts\UploadProductImageServiceInterface;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Infrastructure\Http\Requests\UploadProductImageRequest;
use Modules\Product\Infrastructure\Http\Resources\ProductImageResource;
use OpenApi\Attributes as OA;

class ProductImageController extends AuthorizedController
{
    public function __construct(
        protected UploadProductImageServiceInterface $uploadService,
        protected BulkUploadProductImagesServiceInterface $bulkUploadService,
        protected DeleteProductImageServiceInterface $deleteService,
        protected FindProductImagesServiceInterface $findImagesService,
        protected ImageStorageStrategyInterface $storageStrategy
    ) {}

    #[OA\Get(
        path: '/api/products/{productId}/images',
        summary: 'List product images',
        tags: ['Product Images'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'productId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of product images',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/ProductImageObject'))),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function index(int $productId, Request $request)
    {
        $this->authorize('viewAny', Product::class);
        $images = $this->findImagesService->findByProduct($productId);

        return ProductImageResource::collection($images);
    }

    #[OA\Post(
        path: '/api/products/{productId}/images',
        summary: 'Upload a single product image',
        tags: ['Product Images'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'productId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['file'],
                    properties: [
                        new OA\Property(property: 'file',       type: 'string', format: 'binary'),
                        new OA\Property(property: 'sort_order', type: 'integer', nullable: true, example: 0),
                        new OA\Property(property: 'is_primary', type: 'boolean', nullable: true, example: false),
                        new OA\Property(property: 'metadata',   type: 'string', nullable: true, example: '{}'),
                    ],
                ),
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Image uploaded',
                content: new OA\JsonContent(ref: '#/components/schemas/ProductImageObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function store(UploadProductImageRequest $request, int $productId): ProductImageResource
    {
        $this->authorize('create', Product::class);

        $metadataRaw = $request->input('metadata');
        $decoded     = $metadataRaw ? json_decode($metadataRaw, true) : null;
        $metadata    = is_array($decoded) ? $decoded : null;

        $image = $this->uploadService->execute([
            'product_id' => $productId,
            'file'       => $request->file('file'),
            'sort_order' => $request->integer('sort_order', 0),
            'is_primary' => $request->boolean('is_primary', false),
            'metadata'   => $metadata,
        ]);

        return new ProductImageResource($image);
    }

    #[OA\Post(
        path: '/api/products/{productId}/images/bulk',
        summary: 'Upload multiple product images in one request',
        tags: ['Product Images'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'productId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['files[]'],
                    properties: [
                        new OA\Property(property: 'files[]',           type: 'array',
                            items: new OA\Items(type: 'string', format: 'binary')),
                        new OA\Property(property: 'sort_order_start',  type: 'integer', nullable: true, example: 0),
                        new OA\Property(property: 'is_primary_index',  type: 'integer', nullable: true, example: 0),
                        new OA\Property(property: 'metadata',          type: 'string',  nullable: true, example: '{}'),
                    ],
                ),
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Images uploaded',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/ProductImageObject'))),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function storeMany(UploadProductImageRequest $request, int $productId): JsonResponse
    {
        $this->authorize('create', Product::class);

        $metadataRaw = $request->input('metadata');
        $decoded     = $metadataRaw ? json_decode($metadataRaw, true) : null;
        $metadata    = is_array($decoded) ? $decoded : null;

        $images = $this->bulkUploadService->execute([
            'product_id'        => $productId,
            'files'             => $request->file('files') ?? [],
            'sort_order_start'  => $request->integer('sort_order_start', 0),
            'is_primary_index'  => $request->has('is_primary_index')
                ? $request->integer('is_primary_index')
                : null,
            'metadata'          => is_array($metadata) ? $metadata : null,
        ]);

        return ProductImageResource::collection($images)
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Delete(
        path: '/api/products/{productId}/images/{imageId}',
        summary: 'Delete product image',
        tags: ['Product Images'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'productId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'imageId',   in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Deleted',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function destroy(int $productId, int $imageId): JsonResponse
    {
        $this->authorize('delete', Product::class);
        $this->deleteService->execute(['image_id' => $imageId]);

        return response()->json(['message' => 'Image deleted successfully']);
    }

    #[OA\Get(
        path: '/api/storage/product-images/{uuid}',
        summary: 'Serve product image',
        tags: ['Product Images'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'File stream'),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function serve(string $uuid)
    {
        $image = $this->findImagesService->findByUuid($uuid);
        if (! $image) {
            abort(404);
        }

        return $this->storageStrategy->stream($image->getFilePath());
    }
}

