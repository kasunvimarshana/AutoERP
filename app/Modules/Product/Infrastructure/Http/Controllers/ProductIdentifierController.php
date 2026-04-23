<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Product\Application\Contracts\CreateProductIdentifierServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductIdentifierServiceInterface;
use Modules\Product\Application\Contracts\FindProductIdentifierServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductIdentifierServiceInterface;
use Modules\Product\Domain\Entities\ProductIdentifier;
use Modules\Product\Infrastructure\Http\Requests\ListProductIdentifierRequest;
use Modules\Product\Infrastructure\Http\Requests\StoreProductIdentifierRequest;
use Modules\Product\Infrastructure\Http\Requests\UpdateProductIdentifierRequest;
use Modules\Product\Infrastructure\Http\Resources\ProductIdentifierCollection;
use Modules\Product\Infrastructure\Http\Resources\ProductIdentifierResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductIdentifierController extends AuthorizedController
{
    public function __construct(
        protected CreateProductIdentifierServiceInterface $createProductIdentifierService,
        protected UpdateProductIdentifierServiceInterface $updateProductIdentifierService,
        protected DeleteProductIdentifierServiceInterface $deleteProductIdentifierService,
        protected FindProductIdentifierServiceInterface $findProductIdentifierService,
    ) {}

    public function index(ListProductIdentifierRequest $request): JsonResponse
    {
        $this->authorize('viewAny', ProductIdentifier::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'product_id' => $validated['product_id'] ?? null,
            'variant_id' => $validated['variant_id'] ?? null,
            'technology' => $validated['technology'] ?? null,
            'format' => $validated['format'] ?? null,
            'value' => $validated['value'] ?? null,
            'is_primary' => $validated['is_primary'] ?? null,
            'is_active' => $validated['is_active'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $productIdentifiers = $this->findProductIdentifierService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new ProductIdentifierCollection($productIdentifiers))->response();
    }

    public function store(StoreProductIdentifierRequest $request): JsonResponse
    {
        $this->authorize('create', ProductIdentifier::class);

        $productIdentifier = $this->createProductIdentifierService->execute($request->validated());

        return (new ProductIdentifierResource($productIdentifier))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(int $productIdentifier): ProductIdentifierResource
    {
        $foundProductIdentifier = $this->findProductIdentifierOrFail($productIdentifier);
        $this->authorize('view', $foundProductIdentifier);

        return new ProductIdentifierResource($foundProductIdentifier);
    }

    public function update(UpdateProductIdentifierRequest $request, int $productIdentifier): ProductIdentifierResource
    {
        $foundProductIdentifier = $this->findProductIdentifierOrFail($productIdentifier);
        $this->authorize('update', $foundProductIdentifier);

        $payload = $request->validated();
        $payload['id'] = $productIdentifier;

        return new ProductIdentifierResource($this->updateProductIdentifierService->execute($payload));
    }

    public function destroy(int $productIdentifier): JsonResponse
    {
        $foundProductIdentifier = $this->findProductIdentifierOrFail($productIdentifier);
        $this->authorize('delete', $foundProductIdentifier);

        $this->deleteProductIdentifierService->execute(['id' => $productIdentifier]);

        return Response::json(['message' => 'Product identifier deleted successfully']);
    }

    private function findProductIdentifierOrFail(int $productIdentifierId): ProductIdentifier
    {
        $productIdentifier = $this->findProductIdentifierService->find($productIdentifierId);

        if (! $productIdentifier) {
            throw new NotFoundHttpException('Product identifier not found.');
        }

        return $productIdentifier;
    }
}
