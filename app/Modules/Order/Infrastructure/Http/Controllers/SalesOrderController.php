<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Core\Infrastructure\Http\Controllers\BaseController;
use Modules\Order\Application\Contracts\SalesOrderServiceInterface;
use Modules\Order\Application\DTOs\SalesOrderData;
use Modules\Order\Infrastructure\Http\Resources\SalesOrderResource;
use Modules\Order\Infrastructure\Persistence\Eloquent\Models\SalesOrderModel;

class SalesOrderController extends BaseController
{
    public function __construct(SalesOrderServiceInterface $service)
    {
        parent::__construct($service, SalesOrderResource::class, SalesOrderData::class);
    }

    protected function getModelClass(): string
    {
        return SalesOrderModel::class;
    }

    public function index(Request $request): ResourceCollection
    {
        $filters = array_filter($request->only(['status', 'customer_id', 'payment_status']));
        $paginator = $this->service->list($filters, $request->integer('per_page', 15));

        return SalesOrderResource::collection($paginator);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var \Modules\Order\Application\Contracts\SalesOrderServiceInterface $service */
        $service = $this->service;
        $order = $service->createSalesOrder($request->all());

        return (new SalesOrderResource($order))->response()->setStatusCode(201);
    }

    public function show(string $id): JsonResponse
    {
        return (new SalesOrderResource($this->service->find($id)))->response();
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return (new SalesOrderResource($this->service->update($id, $request->all())))->response();
    }

    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json(null, 204);
    }

    public function confirm(string $id): JsonResponse
    {
        /** @var \Modules\Order\Application\Contracts\SalesOrderServiceInterface $service */
        $service = $this->service;

        return (new SalesOrderResource($service->confirmOrder($id)))->response();
    }

    public function cancel(string $id): JsonResponse
    {
        /** @var \Modules\Order\Application\Contracts\SalesOrderServiceInterface $service */
        $service = $this->service;

        return (new SalesOrderResource($service->cancelOrder($id)))->response();
    }
}
