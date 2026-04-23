<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Product\Application\Contracts\CreateComboItemServiceInterface;
use Modules\Product\Application\Contracts\DeleteComboItemServiceInterface;
use Modules\Product\Application\Contracts\FindComboItemServiceInterface;
use Modules\Product\Application\Contracts\UpdateComboItemServiceInterface;
use Modules\Product\Domain\Entities\ComboItem;
use Modules\Product\Infrastructure\Http\Requests\ListComboItemRequest;
use Modules\Product\Infrastructure\Http\Requests\StoreComboItemRequest;
use Modules\Product\Infrastructure\Http\Requests\UpdateComboItemRequest;
use Modules\Product\Infrastructure\Http\Resources\ComboItemCollection;
use Modules\Product\Infrastructure\Http\Resources\ComboItemResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ComboItemController extends AuthorizedController
{
    public function __construct(
        protected CreateComboItemServiceInterface $createComboItemService,
        protected UpdateComboItemServiceInterface $updateComboItemService,
        protected DeleteComboItemServiceInterface $deleteComboItemService,
        protected FindComboItemServiceInterface $findComboItemService,
    ) {}

    public function index(ListComboItemRequest $request): JsonResponse
    {
        $this->authorize('viewAny', ComboItem::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'combo_product_id' => $validated['combo_product_id'] ?? null,
            'component_product_id' => $validated['component_product_id'] ?? null,
            'is_optional' => $validated['is_optional'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $items = $this->findComboItemService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new ComboItemCollection($items))->response();
    }

    public function store(StoreComboItemRequest $request): JsonResponse
    {
        $this->authorize('create', ComboItem::class);

        $item = $this->createComboItemService->execute($request->validated());

        return (new ComboItemResource($item))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(int $comboItem): ComboItemResource
    {
        $item = $this->findOrFail($comboItem);
        $this->authorize('view', $item);

        return new ComboItemResource($item);
    }

    public function update(UpdateComboItemRequest $request, int $comboItem): ComboItemResource
    {
        $item = $this->findOrFail($comboItem);
        $this->authorize('update', $item);

        $payload = $request->validated();
        $payload['id'] = $comboItem;

        $updated = $this->updateComboItemService->execute($payload);

        return new ComboItemResource($updated);
    }

    public function destroy(int $comboItem): JsonResponse
    {
        $item = $this->findOrFail($comboItem);
        $this->authorize('delete', $item);

        $this->deleteComboItemService->execute(['id' => $comboItem]);

        return Response::json(['message' => 'Combo item deleted successfully']);
    }

    private function findOrFail(int $id): ComboItem
    {
        $item = $this->findComboItemService->find($id);

        if (! $item) {
            throw new NotFoundHttpException('ComboItem not found.');
        }

        return $item;
    }
}
