<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\ServiceCenter\Domain\Entities\ServiceOrder;

/** @mixin ServiceOrder */
class ServiceOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var ServiceOrder $order */
        $order = $this->resource;

        return [
            'id' => $order->getId(),
            'tenant_id' => $order->getTenantId(),
            'asset_id' => $order->getAssetId(),
            'assigned_technician_id' => $order->getAssignedTechnicianId(),
            'order_number' => $order->getOrderNumber(),
            'service_type' => $order->getServiceType(),
            'status' => $order->getStatus(),
            'description' => $order->getDescription(),
            'scheduled_at' => $order->getScheduledAt()?->format('Y-m-d\TH:i:s\Z'),
            'started_at' => $order->getStartedAt()?->format('Y-m-d\TH:i:s\Z'),
            'completed_at' => $order->getCompletedAt()?->format('Y-m-d\TH:i:s\Z'),
            'estimated_cost' => $order->getEstimatedCost(),
            'total_cost' => $order->getTotalCost(),
            'version' => $order->getVersion(),
        ];
    }
}
