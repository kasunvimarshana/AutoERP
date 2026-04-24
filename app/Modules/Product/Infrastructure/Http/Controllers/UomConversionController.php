<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Product\Application\Contracts\CreateUomConversionServiceInterface;
use Modules\Product\Application\Contracts\DeleteUomConversionServiceInterface;
use Modules\Product\Application\Contracts\FindUomConversionServiceInterface;
use Modules\Product\Application\Contracts\UomConversionResolverServiceInterface;
use Modules\Product\Application\Contracts\UpdateUomConversionServiceInterface;
use Modules\Product\Domain\Entities\UomConversion;
use Modules\Product\Infrastructure\Http\Requests\ListUomConversionRequest;
use Modules\Product\Infrastructure\Http\Requests\ResolveUomConversionRequest;
use Modules\Product\Infrastructure\Http\Requests\StoreUomConversionRequest;
use Modules\Product\Infrastructure\Http\Requests\UpdateUomConversionRequest;
use Modules\Product\Infrastructure\Http\Resources\UomConversionCollection;
use Modules\Product\Infrastructure\Http\Resources\UomConversionResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UomConversionController extends AuthorizedController
{
    public function __construct(
        protected CreateUomConversionServiceInterface $createUomConversionService,
        protected UpdateUomConversionServiceInterface $updateUomConversionService,
        protected DeleteUomConversionServiceInterface $deleteUomConversionService,
        protected FindUomConversionServiceInterface $findUomConversionService,
        protected UomConversionResolverServiceInterface $uomConversionResolverService,
    ) {}

    public function index(ListUomConversionRequest $request): JsonResponse
    {
        $this->authorize('viewAny', UomConversion::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => (int) $validated['tenant_id'],
            'product_id' => $validated['product_id'] ?? null,
            'from_uom_id' => $validated['from_uom_id'] ?? null,
            'to_uom_id' => $validated['to_uom_id'] ?? null,
            'is_active' => $validated['is_active'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $uomConversions = $this->findUomConversionService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new UomConversionCollection($uomConversions))->response();
    }

    public function store(StoreUomConversionRequest $request): JsonResponse
    {
        $this->authorize('create', UomConversion::class);

        $payload = $request->validated();
        $payload['tenant_id'] = (int) $payload['tenant_id'];

        $uomConversion = $this->createUomConversionService->execute($payload);

        return (new UomConversionResource($uomConversion))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Request $request, int $uomConversion): UomConversionResource
    {
        $foundUomConversion = $this->findUomConversionOrFail($uomConversion);
        $this->authorize('view', $foundUomConversion);

        return new UomConversionResource($foundUomConversion);
    }

    public function update(UpdateUomConversionRequest $request, int $uomConversion): UomConversionResource
    {
        $foundUomConversion = $this->findUomConversionOrFail($uomConversion);
        $this->authorize('update', $foundUomConversion);

        $payload = $request->validated();
        $payload['id'] = $uomConversion;
        $payload['tenant_id'] = (int) $payload['tenant_id'];

        return new UomConversionResource($this->updateUomConversionService->execute($payload));
    }

    public function resolve(ResolveUomConversionRequest $request): JsonResponse
    {
        $this->authorize('viewAny', UomConversion::class);

        $validated = $request->validated();

        $result = $this->uomConversionResolverService->convertQuantity(
            tenantId: (int) $validated['tenant_id'],
            productId: isset($validated['product_id']) ? (int) $validated['product_id'] : null,
            fromUomId: (int) $validated['from_uom_id'],
            toUomId: (int) $validated['to_uom_id'],
            quantity: (string) $validated['quantity'],
            scale: (int) ($validated['scale'] ?? 6),
        );

        return Response::json(['data' => $result]);
    }

    public function destroy(int $uomConversion): JsonResponse
    {
        $foundUomConversion = $this->findUomConversionOrFail($uomConversion);
        $this->authorize('delete', $foundUomConversion);

        $this->deleteUomConversionService->execute(['id' => $uomConversion]);

        return Response::json(['message' => 'UOM conversion deleted successfully']);
    }

    private function findUomConversionOrFail(int $uomConversionId): UomConversion
    {
        $uomConversion = $this->findUomConversionService->find($uomConversionId);

        if (! $uomConversion) {
            throw new NotFoundHttpException('UOM conversion not found.');
        }

        return $uomConversion;
    }
}
