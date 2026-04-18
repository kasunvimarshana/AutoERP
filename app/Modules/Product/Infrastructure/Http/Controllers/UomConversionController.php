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
use Modules\Product\Application\Contracts\UpdateUomConversionServiceInterface;
use Modules\Product\Domain\Entities\UomConversion;
use Modules\Product\Infrastructure\Http\Requests\ListUomConversionRequest;
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
    ) {}

    public function index(ListUomConversionRequest $request): JsonResponse
    {
        $this->authorize('viewAny', UomConversion::class);
        $validated = $request->validated();

        $filters = array_filter([
            'from_uom_id' => $validated['from_uom_id'] ?? null,
            'to_uom_id' => $validated['to_uom_id'] ?? null,
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

        $uomConversion = $this->createUomConversionService->execute($request->validated());

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

        return new UomConversionResource($this->updateUomConversionService->execute($payload));
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
