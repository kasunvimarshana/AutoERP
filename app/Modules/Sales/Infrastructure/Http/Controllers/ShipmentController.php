<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Sales\Application\Contracts\CreateShipmentServiceInterface;
use Modules\Sales\Application\Contracts\DeleteShipmentServiceInterface;
use Modules\Sales\Application\Contracts\FindShipmentServiceInterface;
use Modules\Sales\Application\Contracts\ProcessShipmentServiceInterface;
use Modules\Sales\Application\Contracts\UpdateShipmentServiceInterface;
use Modules\Sales\Domain\Entities\Shipment;
use Modules\Sales\Infrastructure\Http\Requests\ListShipmentRequest;
use Modules\Sales\Infrastructure\Http\Requests\ProcessShipmentRequest;
use Modules\Sales\Infrastructure\Http\Requests\StoreShipmentRequest;
use Modules\Sales\Infrastructure\Http\Requests\UpdateShipmentRequest;
use Modules\Sales\Infrastructure\Http\Resources\ShipmentCollection;
use Modules\Sales\Infrastructure\Http\Resources\ShipmentResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ShipmentController extends AuthorizedController
{
    public function __construct(
        protected CreateShipmentServiceInterface $createService,
        protected UpdateShipmentServiceInterface $updateService,
        protected DeleteShipmentServiceInterface $deleteService,
        protected FindShipmentServiceInterface $findService,
        protected ProcessShipmentServiceInterface $processService,
    ) {}

    public function index(ListShipmentRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Shipment::class);
        $validated = $request->validated();
        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'customer_id' => $validated['customer_id'] ?? null,
            'sales_order_id' => $validated['sales_order_id'] ?? null,
            'status' => $validated['status'] ?? null,
        ], static fn (mixed $v): bool => $v !== null && $v !== '');

        $result = $this->findService->list(
            filters: $filters,
            perPage: isset($validated['per_page']) ? (int) $validated['per_page'] : null,
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new ShipmentCollection($result))->response();
    }

    public function store(StoreShipmentRequest $request): JsonResponse
    {
        $this->authorize('create', Shipment::class);
        $entity = $this->createService->execute($request->validated());

        return (new ShipmentResource($entity))->response()->setStatusCode(201);
    }

    public function show(int $shipment): ShipmentResource
    {
        $entity = $this->findOrFail($shipment);
        $this->authorize('view', $entity);

        return new ShipmentResource($entity);
    }

    public function update(UpdateShipmentRequest $request, int $shipment): ShipmentResource
    {
        $entity = $this->findOrFail($shipment);
        $this->authorize('update', $entity);
        $payload = $request->validated();
        $payload['id'] = $shipment;
        $updated = $this->updateService->execute($payload);

        return new ShipmentResource($updated);
    }

    public function destroy(int $shipment): JsonResponse
    {
        $entity = $this->findOrFail($shipment);
        $this->authorize('delete', $entity);
        $this->deleteService->execute(['id' => $shipment]);

        return Response::json(['message' => 'Shipment deleted successfully']);
    }

    public function process(ProcessShipmentRequest $request, int $shipment): ShipmentResource
    {
        $entity = $this->findOrFail($shipment);
        $this->authorize('update', $entity);
        $processed = $this->processService->execute(['id' => $shipment]);

        return new ShipmentResource($processed);
    }

    private function findOrFail(int $id): Shipment
    {
        $entity = $this->findService->find($id);
        if (! $entity) {
            throw new NotFoundHttpException('Shipment not found.');
        }

        return $entity;
    }
}
