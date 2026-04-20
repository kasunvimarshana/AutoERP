<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Purchase\Application\Contracts\CreatePurchaseReturnServiceInterface;
use Modules\Purchase\Application\Contracts\DeletePurchaseReturnServiceInterface;
use Modules\Purchase\Application\Contracts\FindPurchaseReturnServiceInterface;
use Modules\Purchase\Application\Contracts\PostPurchaseReturnServiceInterface;
use Modules\Purchase\Application\Contracts\UpdatePurchaseReturnServiceInterface;
use Modules\Purchase\Domain\Entities\PurchaseReturn;
use Modules\Purchase\Infrastructure\Http\Requests\ListPurchaseReturnRequest;
use Modules\Purchase\Infrastructure\Http\Requests\PostPurchaseReturnRequest;
use Modules\Purchase\Infrastructure\Http\Requests\StorePurchaseReturnRequest;
use Modules\Purchase\Infrastructure\Http\Requests\UpdatePurchaseReturnRequest;
use Modules\Purchase\Infrastructure\Http\Resources\PurchaseReturnCollection;
use Modules\Purchase\Infrastructure\Http\Resources\PurchaseReturnResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PurchaseReturnController extends AuthorizedController
{
    public function __construct(
        protected CreatePurchaseReturnServiceInterface $createService,
        protected UpdatePurchaseReturnServiceInterface $updateService,
        protected DeletePurchaseReturnServiceInterface $deleteService,
        protected FindPurchaseReturnServiceInterface $findService,
        protected PostPurchaseReturnServiceInterface $postService,
    ) {}

    public function index(ListPurchaseReturnRequest $request): JsonResponse
    {
        $this->authorize('viewAny', PurchaseReturn::class);
        $validated = $request->validated();
        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'supplier_id' => $validated['supplier_id'] ?? null,
            'status' => $validated['status'] ?? null,
            'return_number' => $validated['return_number'] ?? null,
        ], static fn (mixed $v): bool => $v !== null && $v !== '');

        $result = $this->findService->list(
            filters: $filters,
            perPage: isset($validated['per_page']) ? (int) $validated['per_page'] : null,
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new PurchaseReturnCollection($result))->response();
    }

    public function store(StorePurchaseReturnRequest $request): JsonResponse
    {
        $this->authorize('create', PurchaseReturn::class);
        $entity = $this->createService->execute($request->validated());

        return (new PurchaseReturnResource($entity))->response()->setStatusCode(201);
    }

    public function show(int $purchaseReturn): PurchaseReturnResource
    {
        $entity = $this->findOrFail($purchaseReturn);
        $this->authorize('view', $entity);

        return new PurchaseReturnResource($entity);
    }

    public function update(UpdatePurchaseReturnRequest $request, int $purchaseReturn): PurchaseReturnResource
    {
        $entity = $this->findOrFail($purchaseReturn);
        $this->authorize('update', $entity);
        $payload = $request->validated();
        $payload['id'] = $purchaseReturn;
        $updated = $this->updateService->execute($payload);

        return new PurchaseReturnResource($updated);
    }

    public function destroy(int $purchaseReturn): JsonResponse
    {
        $entity = $this->findOrFail($purchaseReturn);
        $this->authorize('delete', $entity);
        $this->deleteService->execute(['id' => $purchaseReturn]);

        return Response::json(['message' => 'Purchase return deleted successfully']);
    }

    public function post(PostPurchaseReturnRequest $request, int $purchaseReturn): PurchaseReturnResource
    {
        $entity = $this->findOrFail($purchaseReturn);
        $this->authorize('update', $entity);
        $posted = $this->postService->execute(['id' => $purchaseReturn]);

        return new PurchaseReturnResource($posted);
    }

    private function findOrFail(int $id): PurchaseReturn
    {
        $entity = $this->findService->find($id);
        if (! $entity) {
            throw new NotFoundHttpException('Purchase return not found.');
        }

        return $entity;
    }
}
