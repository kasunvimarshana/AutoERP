<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Inventory\Application\Contracts\CreateBatchServiceInterface;
use Modules\Inventory\Application\Contracts\DeleteBatchServiceInterface;
use Modules\Inventory\Application\Contracts\FindBatchServiceInterface;
use Modules\Inventory\Application\Contracts\UpdateBatchServiceInterface;
use Modules\Inventory\Domain\Entities\Batch;
use Modules\Inventory\Infrastructure\Http\Requests\ListBatchRequest;
use Modules\Inventory\Infrastructure\Http\Requests\StoreBatchRequest;
use Modules\Inventory\Infrastructure\Http\Requests\UpdateBatchRequest;
use Modules\Inventory\Infrastructure\Http\Resources\BatchResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class InventoryBatchController extends AuthorizedController
{
    public function __construct(
        private readonly CreateBatchServiceInterface $createBatchService,
        private readonly UpdateBatchServiceInterface $updateBatchService,
        private readonly DeleteBatchServiceInterface $deleteBatchService,
        private readonly FindBatchServiceInterface $findBatchService,
    ) {}

    public function index(ListBatchRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Batch::class);

        $validated = $request->validated();

        $paginator = $this->findBatchService->list(
            filters: $validated,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: (string) ($validated['sort'] ?? 'id'),
        );

        return response()->json(BatchResource::collection($paginator));
    }

    public function show(int $batch): JsonResponse
    {
        $this->authorize('view', Batch::class);

        $entity = $this->findBatchService->findById($batch);

        if ($entity === null) {
            return response()->json(['message' => 'Batch not found.'], HttpResponse::HTTP_NOT_FOUND);
        }

        return response()->json(new BatchResource($entity));
    }

    public function store(StoreBatchRequest $request): JsonResponse
    {
        $this->authorize('create', Batch::class);

        $batch = $this->createBatchService->execute($request->validated());

        return response()->json(new BatchResource($batch), HttpResponse::HTTP_CREATED);
    }

    public function update(UpdateBatchRequest $request, int $batch): JsonResponse
    {
        $this->authorize('update', Batch::class);

        $data        = $request->validated();
        $data['id']  = $batch;

        $updated = $this->updateBatchService->execute($data);

        return response()->json(new BatchResource($updated));
    }

    public function destroy(int $batch): JsonResponse
    {
        $this->authorize('delete', Batch::class);

        $this->deleteBatchService->execute(['id' => $batch]);

        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }
}
