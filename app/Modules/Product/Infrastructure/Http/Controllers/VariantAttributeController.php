<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Product\Application\Contracts\CreateVariantAttributeServiceInterface;
use Modules\Product\Application\Contracts\DeleteVariantAttributeServiceInterface;
use Modules\Product\Application\Contracts\FindVariantAttributeServiceInterface;
use Modules\Product\Application\Contracts\UpdateVariantAttributeServiceInterface;
use Modules\Product\Domain\Entities\VariantAttribute;
use Modules\Product\Infrastructure\Http\Requests\StoreVariantAttributeRequest;
use Modules\Product\Infrastructure\Http\Requests\UpdateVariantAttributeRequest;
use Modules\Product\Infrastructure\Http\Resources\VariantAttributeResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class VariantAttributeController extends AuthorizedController
{
    public function __construct(
        private readonly CreateVariantAttributeServiceInterface $createService,
        private readonly UpdateVariantAttributeServiceInterface $updateService,
        private readonly DeleteVariantAttributeServiceInterface $deleteService,
        private readonly FindVariantAttributeServiceInterface $findService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', VariantAttribute::class);

        $validated = $request->validate([
            'tenant_id' => 'nullable|integer|min:1',
            'product_id' => 'nullable|integer|min:1',
            'attribute_id' => 'nullable|integer|min:1',
            'is_required' => 'nullable|boolean',
            'is_variation_axis' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'sort' => 'nullable|string|max:50',
        ]);

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'product_id' => $validated['product_id'] ?? null,
            'attribute_id' => $validated['attribute_id'] ?? null,
            'is_required' => $validated['is_required'] ?? null,
            'is_variation_axis' => $validated['is_variation_axis'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $values = $this->findService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return VariantAttributeResource::collection($values)->response();
    }

    public function store(StoreVariantAttributeRequest $request): JsonResponse
    {
        $this->authorize('create', VariantAttribute::class);
        $payload = $request->validated();

        $value = $this->createService->execute($payload);

        return (new VariantAttributeResource($value))->response()->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(int $variantAttribute): VariantAttributeResource
    {
        $value = $this->findVariantAttributeOrFail($variantAttribute);
        $this->authorize('view', $value);

        return new VariantAttributeResource($value);
    }

    public function update(UpdateVariantAttributeRequest $request, int $variantAttribute): VariantAttributeResource
    {
        $value = $this->findVariantAttributeOrFail($variantAttribute);
        $this->authorize('update', $value);

        $payload = $request->validated();
        $payload['id'] = $variantAttribute;

        return new VariantAttributeResource($this->updateService->execute($payload));
    }

    public function destroy(int $variantAttribute): JsonResponse
    {
        $value = $this->findVariantAttributeOrFail($variantAttribute);
        $this->authorize('delete', $value);

        $this->deleteService->execute(['id' => $variantAttribute]);

        return Response::json(['message' => 'Variant attribute deleted successfully']);
    }

    private function findVariantAttributeOrFail(int $id): VariantAttribute
    {
        $value = $this->findService->find($id);

        if (! $value instanceof VariantAttribute) {
            throw new NotFoundHttpException('Variant attribute not found.');
        }

        return $value;
    }
}
