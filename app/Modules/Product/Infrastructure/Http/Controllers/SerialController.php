<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Product\Application\Contracts\CreateSerialServiceInterface;
use Modules\Product\Application\Contracts\DeleteSerialServiceInterface;
use Modules\Product\Application\Contracts\FindSerialServiceInterface;
use Modules\Product\Application\Contracts\UpdateSerialServiceInterface;
use Modules\Product\Domain\Entities\Serial;
use Modules\Product\Infrastructure\Http\Requests\ListSerialRequest;
use Modules\Product\Infrastructure\Http\Requests\StoreSerialRequest;
use Modules\Product\Infrastructure\Http\Requests\UpdateSerialRequest;
use Modules\Product\Infrastructure\Http\Resources\SerialCollection;
use Modules\Product\Infrastructure\Http\Resources\SerialResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SerialController extends AuthorizedController
{
    public function __construct(
        protected CreateSerialServiceInterface $createSerialService,
        protected UpdateSerialServiceInterface $updateSerialService,
        protected DeleteSerialServiceInterface $deleteSerialService,
        protected FindSerialServiceInterface $findSerialService,
    ) {}

    public function index(ListSerialRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Serial::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'product_id' => $validated['product_id'] ?? null,
            'variant_id' => $validated['variant_id'] ?? null,
            'batch_id' => $validated['batch_id'] ?? null,
            'status' => $validated['status'] ?? null,
            'serial_number' => $validated['serial_number'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $items = $this->findSerialService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new SerialCollection($items))->response();
    }

    public function store(StoreSerialRequest $request): JsonResponse
    {
        $this->authorize('create', Serial::class);

        $item = $this->createSerialService->execute($request->validated());

        return (new SerialResource($item))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(int $serial): SerialResource
    {
        $item = $this->findOrFail($serial);
        $this->authorize('view', $item);

        return new SerialResource($item);
    }

    public function update(UpdateSerialRequest $request, int $serial): SerialResource
    {
        $item = $this->findOrFail($serial);
        $this->authorize('update', $item);

        $payload = $request->validated();
        $payload['id'] = $serial;

        $updated = $this->updateSerialService->execute($payload);

        return new SerialResource($updated);
    }

    public function destroy(int $serial): JsonResponse
    {
        $item = $this->findOrFail($serial);
        $this->authorize('delete', $item);

        $this->deleteSerialService->execute(['id' => $serial]);

        return Response::json(['message' => 'Serial deleted successfully']);
    }

    private function findOrFail(int $id): Serial
    {
        $item = $this->findSerialService->find($id);

        if (! $item) {
            throw new NotFoundHttpException('Serial not found.');
        }

        return $item;
    }
}
