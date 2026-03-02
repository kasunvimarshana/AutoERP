<?php

declare(strict_types=1);

namespace Modules\Pos\Interfaces\Http\Controllers;

use App\Shared\Abstractions\BaseController;
use Illuminate\Http\JsonResponse;
use Modules\Pos\Application\Commands\CancelPosOrderCommand;
use Modules\Pos\Application\Commands\CreatePosOrderCommand;
use Modules\Pos\Application\Commands\DeletePosOrderCommand;
use Modules\Pos\Application\Commands\PayPosOrderCommand;
use Modules\Pos\Application\Commands\RefundPosOrderCommand;
use Modules\Pos\Application\Services\PosOrderService;
use Modules\Pos\Interfaces\Http\Requests\CreatePosOrderRequest;
use Modules\Pos\Interfaces\Http\Requests\PayPosOrderRequest;
use Modules\Pos\Interfaces\Http\Requests\RefundPosOrderRequest;
use Modules\Pos\Interfaces\Http\Resources\PosOrderLineResource;
use Modules\Pos\Interfaces\Http\Resources\PosOrderResource;
use Modules\Pos\Interfaces\Http\Resources\PosPaymentResource;

class PosOrderController extends BaseController
{
    public function __construct(
        private readonly PosOrderService $service,
    ) {}

    public function index(): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $page = (int) request('page', 1);
        $perPage = (int) request('per_page', 25);

        $result = $this->service->findAll($tenantId, $page, $perPage);

        return $this->success(
            data: array_map(
                fn ($o) => (new PosOrderResource($o))->resolve(),
                $result['items']
            ),
            message: 'POS orders retrieved successfully',
            meta: [
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
            ],
        );
    }

    public function store(CreatePosOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->service->createOrder(new CreatePosOrderCommand(
                tenantId: $request->validated('tenant_id'),
                posSessionId: $request->validated('pos_session_id'),
                currency: $request->validated('currency'),
                lines: $request->validated('lines'),
                notes: $request->validated('notes'),
            ));
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }

        return $this->success(
            data: (new PosOrderResource($order))->resolve(),
            message: 'POS order created successfully',
            status: 201,
        );
    }

    public function show(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $order = $this->service->findById($id, $tenantId);

        if ($order === null) {
            return $this->error('POS order not found', status: 404);
        }

        return $this->success(
            data: (new PosOrderResource($order))->resolve(),
            message: 'POS order retrieved successfully',
        );
    }

    public function pay(PayPosOrderRequest $request, int $id): JsonResponse
    {
        try {
            $order = $this->service->payOrder(new PayPosOrderCommand(
                id: $id,
                tenantId: $request->validated('tenant_id'),
                payments: $request->validated('payments'),
            ));
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }

        return $this->success(
            data: (new PosOrderResource($order))->resolve(),
            message: 'POS order paid successfully',
        );
    }

    public function cancel(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $order = $this->service->cancelOrder(new CancelPosOrderCommand($id, $tenantId));
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }

        return $this->success(
            data: (new PosOrderResource($order))->resolve(),
            message: 'POS order cancelled successfully',
        );
    }

    public function refund(RefundPosOrderRequest $request, int $id): JsonResponse
    {
        try {
            $order = $this->service->refundOrder(new RefundPosOrderCommand(
                id: $id,
                tenantId: $request->validated('tenant_id'),
                refundAmount: (string) $request->validated('refund_amount'),
                method: $request->validated('method'),
                notes: $request->validated('notes'),
            ));
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }

        return $this->success(
            data: (new PosOrderResource($order))->resolve(),
            message: 'POS order refunded successfully',
        );
    }

    public function lines(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $lines = $this->service->findLines($id, $tenantId);

        return $this->success(
            data: array_map(
                fn ($l) => (new PosOrderLineResource($l))->resolve(),
                $lines
            ),
            message: 'POS order lines retrieved successfully',
        );
    }

    public function payments(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $payments = $this->service->findPayments($id, $tenantId);

        return $this->success(
            data: array_map(
                fn ($p) => (new PosPaymentResource($p))->resolve(),
                $payments
            ),
            message: 'POS order payments retrieved successfully',
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $this->service->deleteOrder(new DeletePosOrderCommand($id, $tenantId));

            return $this->success(message: 'POS order deleted successfully');
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 404);
        }
    }
}
