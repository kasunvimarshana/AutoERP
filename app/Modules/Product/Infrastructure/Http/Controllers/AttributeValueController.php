<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Product\Application\Contracts\CreateAttributeValueServiceInterface;
use Modules\Product\Application\Contracts\DeleteAttributeValueServiceInterface;
use Modules\Product\Application\Contracts\FindAttributeValueServiceInterface;
use Modules\Product\Application\Contracts\UpdateAttributeValueServiceInterface;
use Modules\Product\Domain\Entities\AttributeValue;
use Modules\Product\Infrastructure\Http\Requests\ListAttributeValueRequest;
use Modules\Product\Infrastructure\Http\Requests\StoreAttributeValueRequest;
use Modules\Product\Infrastructure\Http\Requests\UpdateAttributeValueRequest;
use Modules\Product\Infrastructure\Http\Resources\AttributeValueCollection;
use Modules\Product\Infrastructure\Http\Resources\AttributeValueResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AttributeValueController extends AuthorizedController
{
    public function __construct(
        protected CreateAttributeValueServiceInterface $createAttributeValueService,
        protected UpdateAttributeValueServiceInterface $updateAttributeValueService,
        protected DeleteAttributeValueServiceInterface $deleteAttributeValueService,
        protected FindAttributeValueServiceInterface $findAttributeValueService,
    ) {}

    public function index(ListAttributeValueRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AttributeValue::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'attribute_id' => $validated['attribute_id'] ?? null,
            'is_active' => $validated['is_active'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $items = $this->findAttributeValueService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new AttributeValueCollection($items))->response();
    }

    public function store(StoreAttributeValueRequest $request): JsonResponse
    {
        $this->authorize('create', AttributeValue::class);

        $item = $this->createAttributeValueService->execute($request->validated());

        return (new AttributeValueResource($item))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(int $attributeValue): AttributeValueResource
    {
        $item = $this->findOrFail($attributeValue);
        $this->authorize('view', $item);

        return new AttributeValueResource($item);
    }

    public function update(UpdateAttributeValueRequest $request, int $attributeValue): AttributeValueResource
    {
        $item = $this->findOrFail($attributeValue);
        $this->authorize('update', $item);

        $payload = $request->validated();
        $payload['id'] = $attributeValue;

        $updated = $this->updateAttributeValueService->execute($payload);

        return new AttributeValueResource($updated);
    }

    public function destroy(int $attributeValue): JsonResponse
    {
        $item = $this->findOrFail($attributeValue);
        $this->authorize('delete', $item);

        $this->deleteAttributeValueService->execute(['id' => $attributeValue]);

        return Response::json(['message' => 'Attribute value deleted successfully']);
    }

    private function findOrFail(int $id): AttributeValue
    {
        $item = $this->findAttributeValueService->find($id);

        if (! $item) {
            throw new NotFoundHttpException('AttributeValue not found.');
        }

        return $item;
    }
}
