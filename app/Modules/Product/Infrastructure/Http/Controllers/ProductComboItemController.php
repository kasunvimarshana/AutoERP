<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Product\Application\Contracts\CreateComboItemServiceInterface;
use Modules\Product\Application\Contracts\CreateProductServiceInterface;
use Modules\Product\Application\Contracts\DeleteComboItemServiceInterface;
use Modules\Product\Application\Contracts\FindComboItemsServiceInterface;
use Modules\Product\Application\Contracts\UpdateComboItemServiceInterface;
use Modules\Product\Application\DTOs\ComboItemData;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Infrastructure\Http\Requests\StoreComboItemRequest;
use Modules\Product\Infrastructure\Http\Requests\UpdateComboItemRequest;
use Modules\Product\Infrastructure\Http\Resources\ComboItemResource;
use OpenApi\Attributes as OA;

class ProductComboItemController extends AuthorizedController
{
    public function __construct(
        protected CreateComboItemServiceInterface $createService,
        protected UpdateComboItemServiceInterface $updateService,
        protected DeleteComboItemServiceInterface $deleteService,
        protected CreateProductServiceInterface $productService,
        protected FindComboItemsServiceInterface $comboItemService,
    ) {}

    #[OA\Get(
        path: '/api/products/{productId}/combo-items',
        summary: 'List combo product items',
        tags: ['Product Combo Items'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'productId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of combo items', content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'object'))),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function index(int $productId): JsonResponse
    {
        $this->authorize('viewAny', Product::class);
        $comboItems = $this->comboItemService->findByProduct($productId);

        return response()->json(['data' => ComboItemResource::collection($comboItems)]);
    }

    #[OA\Post(
        path: '/api/products/{productId}/combo-items',
        summary: 'Add item to combo product',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['component_product_id', 'quantity'],
                properties: [
                    new OA\Property(property: 'component_product_id', type: 'integer', example: 5),
                    new OA\Property(property: 'quantity',             type: 'number',  format: 'float', example: 2),
                    new OA\Property(property: 'price_override',       type: 'number',  format: 'float', nullable: true),
                    new OA\Property(property: 'currency',             type: 'string',  nullable: true, maxLength: 3, example: 'USD'),
                    new OA\Property(property: 'sort_order',           type: 'integer', nullable: true, example: 0),
                    new OA\Property(property: 'metadata',             type: 'object',  nullable: true),
                ],
            ),
        ),
        tags: ['Product Combo Items'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'productId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 201, description: 'Combo item added', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function store(StoreComboItemRequest $request, int $productId): JsonResponse
    {
        $this->authorize('create', Product::class);
        $product = $this->productService->find($productId);
        if (! $product) {
            abort(404);
        }

        $validated               = $request->validated();
        $validated['product_id'] = $productId;
        $validated['tenant_id']  = $product->getTenantId();
        $dto                     = ComboItemData::fromArray($validated);

        $comboItem = $this->createService->execute($dto->toArray());

        return (new ComboItemResource($comboItem))->response()->setStatusCode(201);
    }

    #[OA\Put(
        path: '/api/products/{productId}/combo-items/{itemId}',
        summary: 'Update combo item',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['quantity'],
                properties: [
                    new OA\Property(property: 'quantity',       type: 'number',  format: 'float'),
                    new OA\Property(property: 'price_override', type: 'number',  format: 'float', nullable: true),
                    new OA\Property(property: 'currency',       type: 'string',  nullable: true, maxLength: 3),
                    new OA\Property(property: 'sort_order',     type: 'integer', nullable: true),
                    new OA\Property(property: 'metadata',       type: 'object',  nullable: true),
                ],
            ),
        ),
        tags: ['Product Combo Items'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'productId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'itemId',    in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Combo item updated', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function update(UpdateComboItemRequest $request, int $productId, int $itemId): ComboItemResource
    {
        $this->authorize('update', Product::class);
        $comboItem = $this->comboItemService->find($itemId);
        if (! $comboItem) {
            abort(404);
        }

        $validated               = $request->validated();
        $validated['id']         = $itemId;
        $validated['product_id'] = $productId;
        $validated['tenant_id']  = $comboItem->getTenantId();
        $validated['component_product_id'] = $comboItem->getComponentProductId();
        $dto = ComboItemData::fromArray($validated);

        $updated = $this->updateService->execute($dto->toArray());

        return new ComboItemResource($updated);
    }

    #[OA\Delete(
        path: '/api/products/{productId}/combo-items/{itemId}',
        summary: 'Remove item from combo product',
        tags: ['Product Combo Items'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'productId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'itemId',    in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Deleted', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function destroy(int $productId, int $itemId): JsonResponse
    {
        $this->authorize('delete', Product::class);
        $comboItem = $this->comboItemService->find($itemId);
        if (! $comboItem) {
            abort(404);
        }

        $this->deleteService->execute(['id' => $itemId]);

        return response()->json(['message' => 'Combo item removed successfully']);
    }
}
