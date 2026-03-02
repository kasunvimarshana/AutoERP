<?php

declare(strict_types=1);

namespace Modules\Sales\Interfaces\Http\Controllers;

use App\Shared\Abstractions\BaseController;
use DomainException;
use Illuminate\Http\JsonResponse;
use Modules\Sales\Application\Commands\CancelSalesOrderCommand;
use Modules\Sales\Application\Commands\ConfirmSalesOrderCommand;
use Modules\Sales\Application\Commands\CreateSalesOrderCommand;
use Modules\Sales\Application\Commands\DeleteSalesOrderCommand;
use Modules\Sales\Application\Services\SalesOrderService;
use Modules\Sales\Interfaces\Http\Requests\CancelSalesOrderRequest;
use Modules\Sales\Interfaces\Http\Requests\ConfirmSalesOrderRequest;
use Modules\Sales\Interfaces\Http\Requests\CreateSalesOrderRequest;
use Modules\Sales\Interfaces\Http\Resources\SalesOrderResource;

class SalesOrderController extends BaseController
{
    public function __construct(
        private readonly SalesOrderService $salesOrderService,
    ) {}

    public function index(): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $page = (int) request('page', 1);
        $perPage = (int) request('per_page', 25);

        $result = $this->salesOrderService->listSalesOrders($tenantId, $page, $perPage);

        return $this->success(
            data: array_map(
                fn ($order) => (new SalesOrderResource($order))->resolve(),
                $result['items']
            ),
            message: 'Sales orders retrieved successfully',
            meta: [
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
            ],
        );
    }

    public function store(CreateSalesOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->salesOrderService->createSalesOrder(new CreateSalesOrderCommand(
                tenantId: (int) $request->validated('tenant_id'),
                customerName: $request->validated('customer_name'),
                customerEmail: $request->validated('customer_email'),
                customerPhone: $request->validated('customer_phone'),
                orderDate: $request->validated('order_date'),
                dueDate: $request->validated('due_date'),
                notes: $request->validated('notes'),
                currency: $request->validated('currency', config('currency.default', 'LKR')),
                lines: $request->validated('lines'),
            ));

            return $this->success(
                data: (new SalesOrderResource($order))->resolve(),
                message: 'Sales order created successfully',
                status: 201,
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $order = $this->salesOrderService->findSalesOrderById($id, $tenantId);

        if ($order === null) {
            return $this->error('Sales order not found', status: 404);
        }

        return $this->success(
            data: (new SalesOrderResource($order))->resolve(),
            message: 'Sales order retrieved successfully',
        );
    }

    public function confirm(ConfirmSalesOrderRequest $request, int $id): JsonResponse
    {
        try {
            $tenantId = (int) $request->query('tenant_id', '0');

            $order = $this->salesOrderService->confirmSalesOrder(
                new ConfirmSalesOrderCommand(id: $id, tenantId: $tenantId)
            );

            return $this->success(
                data: (new SalesOrderResource($order))->resolve(),
                message: 'Sales order confirmed successfully',
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }
    }

    public function cancel(CancelSalesOrderRequest $request, int $id): JsonResponse
    {
        try {
            $tenantId = (int) $request->query('tenant_id', '0');

            $order = $this->salesOrderService->cancelSalesOrder(
                new CancelSalesOrderCommand(
                    id: $id,
                    tenantId: $tenantId,
                    reason: $request->validated('reason'),
                )
            );

            return $this->success(
                data: (new SalesOrderResource($order))->resolve(),
                message: 'Sales order cancelled successfully',
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $this->salesOrderService->deleteSalesOrder(new DeleteSalesOrderCommand($id, $tenantId));

            return $this->success(message: 'Sales order deleted successfully');
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 404);
        }
    }
}
