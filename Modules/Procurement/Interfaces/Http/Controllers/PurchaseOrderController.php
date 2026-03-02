<?php

declare(strict_types=1);

namespace Modules\Procurement\Interfaces\Http\Controllers;

use App\Shared\Abstractions\BaseController;
use DomainException;
use Illuminate\Http\JsonResponse;
use Modules\Procurement\Application\Commands\CancelPurchaseOrderCommand;
use Modules\Procurement\Application\Commands\ConfirmPurchaseOrderCommand;
use Modules\Procurement\Application\Commands\CreatePurchaseOrderCommand;
use Modules\Procurement\Application\Commands\DeletePurchaseOrderCommand;
use Modules\Procurement\Application\Commands\ReceiveGoodsCommand;
use Modules\Procurement\Application\Services\PurchaseOrderService;
use Modules\Procurement\Interfaces\Http\Requests\CancelPurchaseOrderRequest;
use Modules\Procurement\Interfaces\Http\Requests\ConfirmPurchaseOrderRequest;
use Modules\Procurement\Interfaces\Http\Requests\CreatePurchaseOrderRequest;
use Modules\Procurement\Interfaces\Http\Requests\ReceiveGoodsRequest;
use Modules\Procurement\Interfaces\Http\Resources\PurchaseOrderResource;

class PurchaseOrderController extends BaseController
{
    public function __construct(
        private readonly PurchaseOrderService $purchaseOrderService,
    ) {}

    public function index(): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $page = (int) request('page', 1);
        $perPage = (int) request('per_page', 25);

        $result = $this->purchaseOrderService->listPurchaseOrders($tenantId, $page, $perPage);

        return $this->success(
            data: array_map(
                fn ($order) => (new PurchaseOrderResource($order))->resolve(),
                $result['items']
            ),
            message: 'Purchase orders retrieved successfully',
            meta: [
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
            ],
        );
    }

    public function store(CreatePurchaseOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->purchaseOrderService->createPurchaseOrder(new CreatePurchaseOrderCommand(
                tenantId: (int) $request->validated('tenant_id'),
                supplierId: (int) $request->validated('supplier_id'),
                orderDate: $request->validated('order_date'),
                expectedDeliveryDate: $request->validated('expected_delivery_date'),
                notes: $request->validated('notes'),
                currency: $request->validated('currency', config('currency.default', 'LKR')),
                lines: $request->validated('lines'),
            ));

            return $this->success(
                data: (new PurchaseOrderResource($order))->resolve(),
                message: 'Purchase order created successfully',
                status: 201,
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $order = $this->purchaseOrderService->findPurchaseOrderById($id, $tenantId);

        if ($order === null) {
            return $this->error('Purchase order not found', status: 404);
        }

        return $this->success(
            data: (new PurchaseOrderResource($order))->resolve(),
            message: 'Purchase order retrieved successfully',
        );
    }

    public function confirm(ConfirmPurchaseOrderRequest $request, int $id): JsonResponse
    {
        try {
            $tenantId = (int) $request->query('tenant_id', '0');

            $order = $this->purchaseOrderService->confirmPurchaseOrder(
                new ConfirmPurchaseOrderCommand(id: $id, tenantId: $tenantId)
            );

            return $this->success(
                data: (new PurchaseOrderResource($order))->resolve(),
                message: 'Purchase order confirmed successfully',
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }
    }

    public function receive(ReceiveGoodsRequest $request, int $id): JsonResponse
    {
        try {
            $tenantId = (int) $request->query('tenant_id', '0');

            $order = $this->purchaseOrderService->receiveGoods(new ReceiveGoodsCommand(
                id: $id,
                tenantId: $tenantId,
                warehouseId: (int) $request->validated('warehouse_id'),
                receivedLines: $request->validated('received_lines'),
                notes: $request->validated('notes'),
            ));

            return $this->success(
                data: (new PurchaseOrderResource($order))->resolve(),
                message: 'Goods received successfully',
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }
    }

    public function cancel(CancelPurchaseOrderRequest $request, int $id): JsonResponse
    {
        try {
            $tenantId = (int) $request->query('tenant_id', '0');

            $order = $this->purchaseOrderService->cancelPurchaseOrder(
                new CancelPurchaseOrderCommand(
                    id: $id,
                    tenantId: $tenantId,
                    reason: $request->validated('reason'),
                )
            );

            return $this->success(
                data: (new PurchaseOrderResource($order))->resolve(),
                message: 'Purchase order cancelled successfully',
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $this->purchaseOrderService->deletePurchaseOrder(new DeletePurchaseOrderCommand($id, $tenantId));

            return $this->success(message: 'Purchase order deleted successfully');
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 404);
        }
    }
}
