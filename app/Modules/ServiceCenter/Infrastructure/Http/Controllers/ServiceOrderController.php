<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ServiceCenter\Application\Contracts\CompleteServiceOrderServiceInterface;
use Modules\ServiceCenter\Application\Contracts\CreateServiceOrderServiceInterface;
use Modules\ServiceCenter\Application\DTOs\CompleteServiceOrderDTO;
use Modules\ServiceCenter\Application\DTOs\CreateServiceOrderDTO;
use Modules\ServiceCenter\Domain\RepositoryInterfaces\ServiceOrderRepositoryInterface;
use Modules\ServiceCenter\Domain\RepositoryInterfaces\ServiceTaskRepositoryInterface;
use Modules\ServiceCenter\Domain\RepositoryInterfaces\ServicePartUsageRepositoryInterface;
use Modules\ServiceCenter\Infrastructure\Http\Requests\CompleteServiceOrderRequest;
use Modules\ServiceCenter\Infrastructure\Http\Requests\CreateServiceOrderRequest;
use Modules\ServiceCenter\Infrastructure\Http\Resources\ServiceOrderResource;

class ServiceOrderController extends Controller
{
    public function __construct(
        private readonly CreateServiceOrderServiceInterface $createService,
        private readonly CompleteServiceOrderServiceInterface $completeService,
        private readonly ServiceOrderRepositoryInterface $orders,
        private readonly ServiceTaskRepositoryInterface $tasks,
        private readonly ServicePartUsageRepositoryInterface $partUsages,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->header('X-Tenant-ID', '');
        $page = (int) $request->query('page', 1);
        $limit = min((int) $request->query('limit', 50), 100);
        $status = $request->query('status');
        $assetId = $request->query('asset_id');

        if ($assetId) {
            $result = $this->orders->getByAsset($tenantId, $assetId, $page, $limit);
        } elseif ($status) {
            $result = $this->orders->getByStatus($tenantId, $status, $page, $limit);
        } else {
            $result = $this->orders->getByTenant($tenantId, $page, $limit);
        }

        return response()->json([
            'data' => ServiceOrderResource::collection($result['data']),
            'meta' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'limit' => $result['limit'],
            ],
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->header('X-Tenant-ID', '');
        $order = $this->orders->findById($id);

        if ($order === null || $order->getTenantId() !== $tenantId) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json(new ServiceOrderResource($order));
    }

    public function store(CreateServiceOrderRequest $request): JsonResponse
    {
        $tenantId = $request->header('X-Tenant-ID', '');
        $validated = $request->validated();

        $dto = new CreateServiceOrderDTO(
            tenantId: $tenantId,
            assetId: $validated['asset_id'],
            assignedTechnicianId: $validated['assigned_technician_id'] ?? null,
            serviceType: $validated['service_type'],
            description: $validated['description'] ?? null,
            scheduledAt: isset($validated['scheduled_at']) ? new \DateTime($validated['scheduled_at']) : null,
            estimatedCost: (string) $validated['estimated_cost'],
            tasks: $validated['tasks'] ?? [],
        );

        $order = $this->createService->execute($dto);

        return response()->json(new ServiceOrderResource($order), 201);
    }

    public function complete(CompleteServiceOrderRequest $request, string $id): JsonResponse
    {
        $tenantId = $request->header('X-Tenant-ID', '');
        $validated = $request->validated();

        $dto = new CompleteServiceOrderDTO(
            tenantId: $tenantId,
            serviceOrderId: $id,
            partsUsed: $validated['parts_used'] ?? [],
        );

        $order = $this->completeService->execute($dto);

        return response()->json(new ServiceOrderResource($order));
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->header('X-Tenant-ID', '');
        $order = $this->orders->findById($id);

        if ($order === null || $order->getTenantId() !== $tenantId) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        if ($order->getStatus() === 'completed' || $order->getStatus() === 'cancelled') {
            return response()->json(['message' => 'Cannot cancel a ' . $order->getStatus() . ' order.'], 422);
        }

        $order->cancel();
        $this->orders->update($order);

        return response()->json(new ServiceOrderResource($order));
    }

    public function tasks(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->header('X-Tenant-ID', '');
        $order = $this->orders->findById($id);

        if ($order === null || $order->getTenantId() !== $tenantId) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $tasks = $this->tasks->getByServiceOrder($id);

        return response()->json(['data' => array_map(fn ($t) => [
            'id' => $t->getId(),
            'task_name' => $t->getTaskName(),
            'description' => $t->getDescription(),
            'status' => $t->getStatus(),
            'labor_cost' => $t->getLaborCost(),
            'labor_minutes' => $t->getLaborMinutes(),
        ], $tasks)]);
    }

    public function parts(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->header('X-Tenant-ID', '');
        $order = $this->orders->findById($id);

        if ($order === null || $order->getTenantId() !== $tenantId) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $parts = $this->partUsages->getByServiceOrder($id);

        return response()->json(['data' => array_map(fn ($p) => [
            'id' => $p->getId(),
            'part_name' => $p->getPartName(),
            'part_number' => $p->getPartNumber(),
            'quantity' => $p->getQuantity(),
            'unit_cost' => $p->getUnitCost(),
            'total_cost' => $p->getTotalCost(),
            'inventory_item_id' => $p->getInventoryItemId(),
        ], $parts)]);
    }
}
