<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Inventory\Application\Contracts\ApproveTransferOrderServiceInterface;
use Modules\Inventory\Application\Contracts\CreateTransferOrderServiceInterface;
use Modules\Inventory\Application\Contracts\FindTransferOrderServiceInterface;
use Modules\Inventory\Application\Contracts\ReceiveTransferOrderServiceInterface;
use Modules\Inventory\Infrastructure\Http\Requests\ApproveTransferOrderRequest;
use Modules\Inventory\Infrastructure\Http\Requests\ListTransferOrderRequest;
use Modules\Inventory\Infrastructure\Http\Requests\ReceiveTransferOrderRequest;
use Modules\Inventory\Infrastructure\Http\Requests\StoreTransferOrderRequest;
use Modules\Inventory\Infrastructure\Http\Resources\TransferOrderResource;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Inventory\Domain\Entities\TransferOrder;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class InventoryTransferOrderController extends AuthorizedController
{
    public function __construct(
        private readonly CreateTransferOrderServiceInterface $createTransferOrderService,
        private readonly FindTransferOrderServiceInterface $findTransferOrderService,
        private readonly ApproveTransferOrderServiceInterface $approveTransferOrderService,
        private readonly ReceiveTransferOrderServiceInterface $receiveTransferOrderService,
    ) {}

    public function index(ListTransferOrderRequest $request): JsonResponse
    {
        $this->authorize('viewAny', TransferOrder::class);
        $validated = $request->validated();

        $orders = $this->findTransferOrderService->list(
            tenantId: (int) $validated['tenant_id'],
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
        );

        return response()->json($orders);
    }

    public function show(ListTransferOrderRequest $request, int $transferOrder): JsonResponse
    {
        $validated = $request->validated();

        $order = $this->findTransferOrderService->find((int) $validated['tenant_id'], $transferOrder);
        if ($order === null) {
            return response()->json(['message' => 'Transfer order not found.'], HttpResponse::HTTP_NOT_FOUND);
        }

        $this->authorize('view', $order);

        return (new TransferOrderResource($order))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_OK);
    }

    public function store(StoreTransferOrderRequest $request): JsonResponse
    {
        $this->authorize('create', TransferOrder::class);
        $order = $this->createTransferOrderService->execute($request->validated());

        return (new TransferOrderResource($order))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function approve(ApproveTransferOrderRequest $request, int $transferOrder): JsonResponse
    {
        $this->authorize('update', TransferOrder::class);
        $validated = $request->validated();
        $order = $this->approveTransferOrderService->execute((int) $validated['tenant_id'], $transferOrder);

        return (new TransferOrderResource($order))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_OK);
    }

    public function receive(ReceiveTransferOrderRequest $request, int $transferOrder): JsonResponse
    {
        $this->authorize('update', TransferOrder::class);
        $validated = $request->validated();
        $order = $this->receiveTransferOrderService->execute(
            (int) $validated['tenant_id'],
            $transferOrder,
            array_map(static fn (array $line): array => [
                'line_id' => (int) $line['line_id'],
                'received_qty' => (string) $line['received_qty'],
            ], $validated['lines']),
        );

        return (new TransferOrderResource($order))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_OK);
    }
}
