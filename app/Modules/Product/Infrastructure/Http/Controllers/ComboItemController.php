<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Product\Application\Contracts\CreateComboItemServiceInterface;
use Modules\Product\Application\Contracts\DeleteComboItemServiceInterface;
use Modules\Product\Application\Contracts\FindComboItemServiceInterface;
use Modules\Product\Application\Contracts\UpdateComboItemServiceInterface;
use Modules\Product\Domain\Entities\ComboItem;
use Modules\Product\Infrastructure\Http\Requests\StoreComboItemRequest;
use Modules\Product\Infrastructure\Http\Requests\UpdateComboItemRequest;
use Modules\Product\Infrastructure\Http\Resources\ComboItemResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ComboItemController extends AuthorizedController
{
    public function __construct(
        private readonly CreateComboItemServiceInterface $createService,
        private readonly UpdateComboItemServiceInterface $updateService,
        private readonly DeleteComboItemServiceInterface $deleteService,
        private readonly FindComboItemServiceInterface $findService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ComboItem::class);

        $validated = $request->validate([
            'tenant_id' => 'nullable|integer|min:1',
            'combo_product_id' => 'nullable|integer|min:1',
            'component_product_id' => 'nullable|integer|min:1',
            'component_variant_id' => 'nullable|integer|min:1',
            'uom_id' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'sort' => 'nullable|string|max:50',
        ]);

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'combo_product_id' => $validated['combo_product_id'] ?? null,
            'component_product_id' => $validated['component_product_id'] ?? null,
            'component_variant_id' => $validated['component_variant_id'] ?? null,
            'uom_id' => $validated['uom_id'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $values = $this->findService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return ComboItemResource::collection($values)->response();
    }

    public function store(StoreComboItemRequest $request): JsonResponse
    {
        $this->authorize('create', ComboItem::class);
        $payload = $request->validated();

        $value = $this->createService->execute($payload);

        return (new ComboItemResource($value))->response()->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(int $comboItem): ComboItemResource
    {
        $value = $this->findComboItemOrFail($comboItem);
        $this->authorize('view', $value);

        return new ComboItemResource($value);
    }

    public function update(UpdateComboItemRequest $request, int $comboItem): ComboItemResource
    {
        $value = $this->findComboItemOrFail($comboItem);
        $this->authorize('update', $value);

        $payload = $request->validated();
        $payload['id'] = $comboItem;

        return new ComboItemResource($this->updateService->execute($payload));
    }

    public function destroy(int $comboItem): JsonResponse
    {
        $value = $this->findComboItemOrFail($comboItem);
        $this->authorize('delete', $value);

        $this->deleteService->execute(['id' => $comboItem]);

        return Response::json(['message' => 'Combo item deleted successfully']);
    }

    private function findComboItemOrFail(int $id): ComboItem
    {
        $value = $this->findService->find($id);

        if (! $value instanceof ComboItem) {
            throw new NotFoundHttpException('Combo item not found.');
        }

        return $value;
    }
}
