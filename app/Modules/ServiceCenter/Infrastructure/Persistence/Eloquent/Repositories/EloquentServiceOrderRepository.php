<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\ServiceCenter\Domain\Entities\ServiceOrder;
use Modules\ServiceCenter\Domain\RepositoryInterfaces\ServiceOrderRepositoryInterface;
use Modules\ServiceCenter\Infrastructure\Persistence\Eloquent\Models\ServiceOrderModel;

class EloquentServiceOrderRepository implements ServiceOrderRepositoryInterface
{
    public function create(ServiceOrder $order): void
    {
        ServiceOrderModel::create([
            'id' => $order->getId(),
            'tenant_id' => $order->getTenantId(),
            'asset_id' => $order->getAssetId(),
            'assigned_technician_id' => $order->getAssignedTechnicianId(),
            'order_number' => $order->getOrderNumber(),
            'service_type' => $order->getServiceType(),
            'status' => $order->getStatus(),
            'description' => $order->getDescription(),
            'scheduled_at' => $order->getScheduledAt(),
            'started_at' => $order->getStartedAt(),
            'completed_at' => $order->getCompletedAt(),
            'estimated_cost' => $order->getEstimatedCost(),
            'total_cost' => $order->getTotalCost(),
            'version' => $order->getVersion(),
        ]);
    }

    public function findById(string $id): ?ServiceOrder
    {
        $model = ServiceOrderModel::find($id);
        return $model ? $this->toDomain($model) : null;
    }

    public function findByOrderNumber(string $tenantId, string $orderNumber): ?ServiceOrder
    {
        $model = ServiceOrderModel::byTenant($tenantId)
            ->where('order_number', $orderNumber)
            ->first();
        return $model ? $this->toDomain($model) : null;
    }

    public function getByTenant(string $tenantId, int $page = 1, int $limit = 50): array
    {
        $query = ServiceOrderModel::byTenant($tenantId)->orderByDesc('created_at');
        return $this->paginate($query, $page, $limit);
    }

    public function getByAsset(string $tenantId, string $assetId, int $page = 1, int $limit = 50): array
    {
        $query = ServiceOrderModel::byTenant($tenantId)
            ->where('asset_id', $assetId)
            ->orderByDesc('created_at');
        return $this->paginate($query, $page, $limit);
    }

    public function getByStatus(string $tenantId, string $status, int $page = 1, int $limit = 50): array
    {
        $query = ServiceOrderModel::byTenant($tenantId)->byStatus($status)->orderByDesc('created_at');
        return $this->paginate($query, $page, $limit);
    }

    public function update(ServiceOrder $order): void
    {
        ServiceOrderModel::findOrFail($order->getId())->update([
            'assigned_technician_id' => $order->getAssignedTechnicianId(),
            'status' => $order->getStatus(),
            'description' => $order->getDescription(),
            'scheduled_at' => $order->getScheduledAt(),
            'started_at' => $order->getStartedAt(),
            'completed_at' => $order->getCompletedAt(),
            'estimated_cost' => $order->getEstimatedCost(),
            'total_cost' => $order->getTotalCost(),
            'version' => $order->getVersion(),
        ]);
    }

    private function paginate($query, int $page, int $limit): array
    {
        $total = $query->count();
        $data = $query->paginate($limit, ['*'], 'page', $page)->items();
        return [
            'data' => array_map(fn ($m) => $this->toDomain($m), $data),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    private function toDomain(ServiceOrderModel $model): ServiceOrder
    {
        return new ServiceOrder(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            assetId: (string) $model->asset_id,
            assignedTechnicianId: $model->assigned_technician_id !== null ? (string) $model->assigned_technician_id : null,
            orderNumber: (string) $model->order_number,
            serviceType: (string) $model->service_type,
            status: (string) $model->status,
            description: $model->description !== null ? (string) $model->description : null,
            scheduledAt: $model->scheduled_at?->toDateTime(),
            startedAt: $model->started_at?->toDateTime(),
            completedAt: $model->completed_at?->toDateTime(),
            estimatedCost: (string) $model->estimated_cost,
            totalCost: (string) $model->total_cost,
            version: (int) $model->version,
        );
    }
}
