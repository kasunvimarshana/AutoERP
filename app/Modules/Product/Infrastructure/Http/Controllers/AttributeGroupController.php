<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Product\Application\Contracts\CreateAttributeGroupServiceInterface;
use Modules\Product\Application\Contracts\DeleteAttributeGroupServiceInterface;
use Modules\Product\Application\Contracts\FindAttributeGroupServiceInterface;
use Modules\Product\Application\Contracts\UpdateAttributeGroupServiceInterface;
use Modules\Product\Domain\Entities\AttributeGroup;
use Modules\Product\Infrastructure\Http\Requests\ListAttributeGroupRequest;
use Modules\Product\Infrastructure\Http\Requests\StoreAttributeGroupRequest;
use Modules\Product\Infrastructure\Http\Requests\UpdateAttributeGroupRequest;
use Modules\Product\Infrastructure\Http\Resources\AttributeGroupCollection;
use Modules\Product\Infrastructure\Http\Resources\AttributeGroupResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AttributeGroupController extends AuthorizedController
{
    public function __construct(
        protected CreateAttributeGroupServiceInterface $createAttributeGroupService,
        protected UpdateAttributeGroupServiceInterface $updateAttributeGroupService,
        protected DeleteAttributeGroupServiceInterface $deleteAttributeGroupService,
        protected FindAttributeGroupServiceInterface $findAttributeGroupService,
    ) {}

    public function index(ListAttributeGroupRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AttributeGroup::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'name' => $validated['name'] ?? null,
            'code' => $validated['code'] ?? null,
            'is_active' => $validated['is_active'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $items = $this->findAttributeGroupService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new AttributeGroupCollection($items))->response();
    }

    public function store(StoreAttributeGroupRequest $request): JsonResponse
    {
        $this->authorize('create', AttributeGroup::class);

        $item = $this->createAttributeGroupService->execute($request->validated());

        return (new AttributeGroupResource($item))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(int $attributeGroup): AttributeGroupResource
    {
        $item = $this->findOrFail($attributeGroup);
        $this->authorize('view', $item);

        return new AttributeGroupResource($item);
    }

    public function update(UpdateAttributeGroupRequest $request, int $attributeGroup): AttributeGroupResource
    {
        $item = $this->findOrFail($attributeGroup);
        $this->authorize('update', $item);

        $payload = $request->validated();
        $payload['id'] = $attributeGroup;

        $updated = $this->updateAttributeGroupService->execute($payload);

        return new AttributeGroupResource($updated);
    }

    public function destroy(int $attributeGroup): JsonResponse
    {
        $item = $this->findOrFail($attributeGroup);
        $this->authorize('delete', $item);

        $this->deleteAttributeGroupService->execute(['id' => $attributeGroup]);

        return Response::json(['message' => 'Attribute group deleted successfully']);
    }

    private function findOrFail(int $id): AttributeGroup
    {
        $item = $this->findAttributeGroupService->find($id);

        if (! $item) {
            throw new NotFoundHttpException('AttributeGroup not found.');
        }

        return $item;
    }
}
