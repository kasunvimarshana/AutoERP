<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Interfaces\Http\Controllers;

use App\Shared\Abstractions\BaseController;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\Application\Commands\CancelStorefrontOrderCommand;
use Modules\Ecommerce\Application\Commands\DeleteStorefrontOrderCommand;
use Modules\Ecommerce\Application\Commands\UpdateStorefrontOrderStatusCommand;
use Modules\Ecommerce\Application\Services\StorefrontOrderService;
use Modules\Ecommerce\Interfaces\Http\Requests\UpdateStorefrontOrderStatusRequest;
use Modules\Ecommerce\Interfaces\Http\Resources\StorefrontOrderLineResource;
use Modules\Ecommerce\Interfaces\Http\Resources\StorefrontOrderResource;

class StorefrontOrderController extends BaseController
{
    public function __construct(
        private readonly StorefrontOrderService $service,
    ) {}

    public function index(): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $page = (int) request('page', 1);
        $perPage = (int) request('per_page', 25);

        $result = $this->service->findAll($tenantId, $page, $perPage);

        return $this->success(
            data: array_map(
                fn ($o) => (new StorefrontOrderResource($o))->resolve(),
                $result['items']
            ),
            message: 'Storefront orders retrieved successfully',
            meta: [
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
            ],
        );
    }

    public function show(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $order = $this->service->findById($id, $tenantId);

        if ($order === null) {
            return $this->error('Storefront order not found', status: 404);
        }

        return $this->success(
            data: (new StorefrontOrderResource($order))->resolve(),
            message: 'Storefront order retrieved successfully',
        );
    }

    public function updateStatus(UpdateStorefrontOrderStatusRequest $request, int $id): JsonResponse
    {
        try {
            $order = $this->service->updateStatus(new UpdateStorefrontOrderStatusCommand(
                id: $id,
                tenantId: $request->validated('tenant_id'),
                status: $request->validated('status'),
            ));
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }

        return $this->success(
            data: (new StorefrontOrderResource($order))->resolve(),
            message: 'Order status updated successfully',
        );
    }

    public function cancel(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $order = $this->service->cancel(new CancelStorefrontOrderCommand($id, $tenantId));

            return $this->success(
                data: (new StorefrontOrderResource($order))->resolve(),
                message: 'Order cancelled successfully',
            );
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }
    }

    public function lines(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $order = $this->service->findById($id, $tenantId);

        if ($order === null) {
            return $this->error('Storefront order not found', status: 404);
        }

        $lines = $this->service->findLines($id, $tenantId);

        return $this->success(
            data: array_map(
                fn ($l) => (new StorefrontOrderLineResource($l))->resolve(),
                $lines
            ),
            message: 'Order lines retrieved successfully',
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $this->service->delete(new DeleteStorefrontOrderCommand($id, $tenantId));

            return $this->success(message: 'Storefront order deleted successfully');
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 404);
        }
    }
}
