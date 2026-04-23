<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Product\Application\Contracts\DeleteAttributeServiceInterface;
use Modules\Product\Application\Contracts\FindAttributeServiceInterface;
use Modules\Product\Application\Contracts\UpdateAttributeServiceInterface;
use Modules\Product\Domain\Entities\Attribute;
use Modules\Product\Infrastructure\Http\Requests\ListAttributeRequest;
use Modules\Product\Infrastructure\Http\Requests\StoreAttributeRequest;
use Modules\Product\Infrastructure\Http\Requests\UpdateAttributeRequest;
use Modules\Product\Infrastructure\Http\Resources\AttributeCollection;
use Modules\Product\Infrastructure\Http\Resources\AttributeResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AttributeController extends AuthorizedController
{
    public function __construct(
        protected CreateAttributeServiceInterface $createAttributeService,
        protected UpdateAttributeServiceInterface $updateAttributeService,
        protected DeleteAttributeServiceInterface $deleteAttributeService,
        protected FindAttributeServiceInterface $findAttributeService,
    ) {}

    public function index(ListAttributeRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Attribute::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'group_id' => $validated['group_id'] ?? null,
            'name' => $validated['name'] ?? null,
            'type' => $validated['type'] ?? null,
            'is_active' => $validated['is_active'] ?? null,
            'is_filterable' => $validated['is_filterable'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $items = $this->findAttributeService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new AttributeCollection($items))->response();
    }

    public function store(StoreAttributeRequest $request): JsonResponse
    {
        $this->authorize('create', Attribute::class);

        $item = $this->createAttributeService->execute($request->validated());

        return (new AttributeResource($item))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(int $attribute): AttributeResource
    {
        $item = $this->findOrFail($attribute);
        $this->authorize('view', $item);

        return new AttributeResource($item);
    }

    public function update(UpdateAttributeRequest $request, int $attribute): AttributeResource
    {
        $item = $this->findOrFail($attribute);
        $this->authorize('update', $item);

        $payload = $request->validated();
        $payload['id'] = $attribute;

        $updated = $this->updateAttributeService->execute($payload);

        return new AttributeResource($updated);
    }

    public function destroy(int $attribute): JsonResponse
    {
        $item = $this->findOrFail($attribute);
        $this->authorize('delete', $item);

        $this->deleteAttributeService->execute(['id' => $attribute]);

        return Response::json(['message' => 'Attribute deleted successfully']);
    }

    private function findOrFail(int $id): Attribute
    {
        $item = $this->findAttributeService->find($id);

        if (! $item) {
            throw new NotFoundHttpException('Attribute not found.');
        }

        return $item;
    }
}
