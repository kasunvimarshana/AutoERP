<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Product\Application\Contracts\CreateUnitOfMeasureServiceInterface;
use Modules\Product\Application\Contracts\DeleteUnitOfMeasureServiceInterface;
use Modules\Product\Application\Contracts\FindUnitOfMeasureServiceInterface;
use Modules\Product\Application\Contracts\UpdateUnitOfMeasureServiceInterface;
use Modules\Product\Domain\Entities\UnitOfMeasure;
use Modules\Product\Infrastructure\Http\Requests\ListUnitOfMeasureRequest;
use Modules\Product\Infrastructure\Http\Requests\StoreUnitOfMeasureRequest;
use Modules\Product\Infrastructure\Http\Requests\UpdateUnitOfMeasureRequest;
use Modules\Product\Infrastructure\Http\Resources\UnitOfMeasureCollection;
use Modules\Product\Infrastructure\Http\Resources\UnitOfMeasureResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UnitOfMeasureController extends AuthorizedController
{
    public function __construct(
        protected CreateUnitOfMeasureServiceInterface $createUnitOfMeasureService,
        protected UpdateUnitOfMeasureServiceInterface $updateUnitOfMeasureService,
        protected DeleteUnitOfMeasureServiceInterface $deleteUnitOfMeasureService,
        protected FindUnitOfMeasureServiceInterface $findUnitOfMeasureService,
    ) {}

    public function index(ListUnitOfMeasureRequest $request): JsonResponse
    {
        $this->authorize('viewAny', UnitOfMeasure::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'name' => $validated['name'] ?? null,
            'symbol' => $validated['symbol'] ?? null,
            'type' => $validated['type'] ?? null,
            'is_base' => $validated['is_base'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $units = $this->findUnitOfMeasureService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new UnitOfMeasureCollection($units))->response();
    }

    public function store(StoreUnitOfMeasureRequest $request): JsonResponse
    {
        $this->authorize('create', UnitOfMeasure::class);

        $unitOfMeasure = $this->createUnitOfMeasureService->execute($request->validated());

        return (new UnitOfMeasureResource($unitOfMeasure))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Request $request, int $unitOfMeasure): UnitOfMeasureResource
    {
        $foundUnit = $this->findUnitOfMeasureOrFail($unitOfMeasure);
        $this->authorize('view', $foundUnit);

        return new UnitOfMeasureResource($foundUnit);
    }

    public function update(UpdateUnitOfMeasureRequest $request, int $unitOfMeasure): UnitOfMeasureResource
    {
        $foundUnit = $this->findUnitOfMeasureOrFail($unitOfMeasure);
        $this->authorize('update', $foundUnit);

        $payload = $request->validated();
        $payload['id'] = $unitOfMeasure;

        return new UnitOfMeasureResource($this->updateUnitOfMeasureService->execute($payload));
    }

    public function destroy(int $unitOfMeasure): JsonResponse
    {
        $foundUnit = $this->findUnitOfMeasureOrFail($unitOfMeasure);
        $this->authorize('delete', $foundUnit);

        $this->deleteUnitOfMeasureService->execute(['id' => $unitOfMeasure]);

        return Response::json(['message' => 'Unit of measure deleted successfully']);
    }

    private function findUnitOfMeasureOrFail(int $unitOfMeasureId): UnitOfMeasure
    {
        $unit = $this->findUnitOfMeasureService->find($unitOfMeasureId);

        if (! $unit) {
            throw new NotFoundHttpException('Unit of measure not found.');
        }

        return $unit;
    }
}
