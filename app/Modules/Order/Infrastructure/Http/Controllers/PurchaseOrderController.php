<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Core\Infrastructure\Http\Controllers\BaseController;
use Modules\Order\Application\Contracts\PurchaseOrderServiceInterface;
use Modules\Order\Application\DTOs\PurchaseOrderData;
use Modules\Order\Infrastructure\Http\Resources\PurchaseOrderResource;
use Modules\Order\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderModel;

class PurchaseOrderController extends BaseController
{
    public function __construct(PurchaseOrderServiceInterface $service)
    {
        parent::__construct($service, PurchaseOrderResource::class, PurchaseOrderData::class);
    }

    protected function getModelClass(): string
    {
        return PurchaseOrderModel::class;
    }

    public function index(Request $request): ResourceCollection
    {
        $filters = array_filter($request->only(['status', 'supplier_id', 'payment_status']));
        $paginator = $this->service->list($filters, $request->integer('per_page', 15));

        return PurchaseOrderResource::collection($paginator);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var \Modules\Order\Application\Contracts\PurchaseOrderServiceInterface $service */
        $service = $this->service;
        $order = $service->createPurchaseOrder($request->all());

        return (new PurchaseOrderResource($order))->response()->setStatusCode(201);
    }

    public function show(string $id): JsonResponse
    {
        return (new PurchaseOrderResource($this->service->find($id)))->response();
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return (new PurchaseOrderResource($this->service->update($id, $request->all())))->response();
    }

    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json(null, 204);
    }

    public function receive(Request $request, string $id): JsonResponse
    {
        /** @var \Modules\Order\Application\Contracts\PurchaseOrderServiceInterface $service */
        $service = $this->service;
        $order = $service->receiveOrder($id, $request->input('receipts', []));

        return (new PurchaseOrderResource($order))->response();
    }

    public function cancel(string $id): JsonResponse
    {
        /** @var \Modules\Order\Application\Contracts\PurchaseOrderServiceInterface $service */
        $service = $this->service;

        return (new PurchaseOrderResource($service->cancelOrder($id)))->response();
    }
}
