<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Purchase\Application\Contracts\CreateGrnServiceInterface;
use Modules\Purchase\Application\Contracts\DeleteGrnServiceInterface;
use Modules\Purchase\Application\Contracts\FindGrnServiceInterface;
use Modules\Purchase\Application\Contracts\PostGrnServiceInterface;
use Modules\Purchase\Application\Contracts\UpdateGrnServiceInterface;
use Modules\Purchase\Domain\Entities\GrnHeader;
use Modules\Purchase\Infrastructure\Http\Requests\ListGrnRequest;
use Modules\Purchase\Infrastructure\Http\Requests\PostGrnRequest;
use Modules\Purchase\Infrastructure\Http\Requests\StoreGrnRequest;
use Modules\Purchase\Infrastructure\Http\Requests\UpdateGrnRequest;
use Modules\Purchase\Infrastructure\Http\Resources\GrnHeaderCollection;
use Modules\Purchase\Infrastructure\Http\Resources\GrnHeaderResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GrnController extends AuthorizedController
{
    public function __construct(
        protected CreateGrnServiceInterface $createService,
        protected UpdateGrnServiceInterface $updateService,
        protected DeleteGrnServiceInterface $deleteService,
        protected FindGrnServiceInterface $findService,
        protected PostGrnServiceInterface $postService,
    ) {}

    public function index(ListGrnRequest $request): JsonResponse
    {
        $this->authorize('viewAny', GrnHeader::class);
        $validated = $request->validated();
        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'supplier_id' => $validated['supplier_id'] ?? null,
            'warehouse_id' => $validated['warehouse_id'] ?? null,
            'purchase_order_id' => $validated['purchase_order_id'] ?? null,
            'status' => $validated['status'] ?? null,
        ], static fn (mixed $v): bool => $v !== null && $v !== '');

        $result = $this->findService->list(
            filters: $filters,
            perPage: isset($validated['per_page']) ? (int) $validated['per_page'] : null,
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new GrnHeaderCollection($result))->response();
    }

    public function store(StoreGrnRequest $request): JsonResponse
    {
        $this->authorize('create', GrnHeader::class);
        $entity = $this->createService->execute($request->validated());

        return (new GrnHeaderResource($entity))->response()->setStatusCode(201);
    }

    public function show(int $grn): GrnHeaderResource
    {
        $entity = $this->findOrFail($grn);
        $this->authorize('view', $entity);

        return new GrnHeaderResource($entity);
    }

    public function update(UpdateGrnRequest $request, int $grn): GrnHeaderResource
    {
        $entity = $this->findOrFail($grn);
        $this->authorize('update', $entity);
        $payload = $request->validated();
        $payload['id'] = $grn;
        $updated = $this->updateService->execute($payload);

        return new GrnHeaderResource($updated);
    }

    public function destroy(int $grn): JsonResponse
    {
        $entity = $this->findOrFail($grn);
        $this->authorize('delete', $entity);
        $this->deleteService->execute(['id' => $grn]);

        return Response::json(['message' => 'GRN deleted successfully']);
    }

    public function post(PostGrnRequest $request, int $grn): GrnHeaderResource
    {
        $entity = $this->findOrFail($grn);
        $this->authorize('update', $entity);
        $posted = $this->postService->execute(['id' => $grn]);

        return new GrnHeaderResource($posted);
    }

    private function findOrFail(int $id): GrnHeader
    {
        $entity = $this->findService->find($id);
        if (! $entity) {
            throw new NotFoundHttpException('GRN not found.');
        }

        return $entity;
    }
}
