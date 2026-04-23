<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Product\Application\Contracts\CreateBatchServiceInterface;
use Modules\Product\Application\Contracts\DeleteBatchServiceInterface;
use Modules\Product\Application\Contracts\FindBatchServiceInterface;
use Modules\Product\Application\Contracts\UpdateBatchServiceInterface;
use Modules\Product\Domain\Entities\Batch;
use Modules\Product\Infrastructure\Http\Requests\ListBatchRequest;
use Modules\Product\Infrastructure\Http\Requests\StoreBatchRequest;
use Modules\Product\Infrastructure\Http\Requests\UpdateBatchRequest;
use Modules\Product\Infrastructure\Http\Resources\BatchCollection;
use Modules\Product\Infrastructure\Http\Resources\BatchResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BatchController extends AuthorizedController
{
    public function __construct(
        protected CreateBatchServiceInterface $createBatchService,
        protected UpdateBatchServiceInterface $updateBatchService,
        protected DeleteBatchServiceInterface $deleteBatchService,
        protected FindBatchServiceInterface $findBatchService,
    ) {}

    public function index(ListBatchRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Batch::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'product_id' => $validated['product_id'] ?? null,
            'variant_id' => $validated['variant_id'] ?? null,
            'status' => $validated['status'] ?? null,
            'batch_number' => $validated['batch_number'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $items = $this->findBatchService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new BatchCollection($items))->response();
    }

    public function store(StoreBatchRequest $request): JsonResponse
    {
        $this->authorize('create', Batch::class);

        $item = $this->createBatchService->execute($request->validated());

        return (new BatchResource($item))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(int $batch): BatchResource
    {
        $item = $this->findOrFail($batch);
        $this->authorize('view', $item);

        return new BatchResource($item);
    }

    public function update(UpdateBatchRequest $request, int $batch): BatchResource
    {
        $item = $this->findOrFail($batch);
        $this->authorize('update', $item);

        $payload = $request->validated();
        $payload['id'] = $batch;

        $updated = $this->updateBatchService->execute($payload);

        return new BatchResource($updated);
    }

    public function destroy(int $batch): JsonResponse
    {
        $item = $this->findOrFail($batch);
        $this->authorize('delete', $item);

        $this->deleteBatchService->execute(['id' => $batch]);

        return Response::json(['message' => 'Batch deleted successfully']);
    }

    private function findOrFail(int $id): Batch
    {
        $item = $this->findBatchService->find($id);

        if (! $item) {
            throw new NotFoundHttpException('Batch not found.');
        }

        return $item;
    }
}
