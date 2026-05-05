<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Service\Domain\Entities\ServicePart;

class ServicePartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var ServicePart $part */
        $part = $this->resource;

        return [
            'id' => $part->getId(),
            'tenant_id' => $part->getTenantId(),
            'org_unit_id' => $part->getOrgUnitId(),
            'service_work_order_id' => $part->getServiceWorkOrderId(),
            'service_task_id' => $part->getServiceTaskId(),
            'product_id' => $part->getProductId(),
            'part_source' => $part->getPartSource(),
            'description' => $part->getDescription(),
            'quantity' => $part->getQuantity(),
            'uom_id' => $part->getUomId(),
            'unit_cost' => $part->getUnitCost(),
            'unit_price' => $part->getUnitPrice(),
            'line_amount' => $part->getLineAmount(),
            'is_returned' => $part->isReturned(),
            'is_warranty_covered' => $part->isWarrantyCovered(),
            'stock_reference_type' => $part->getStockReferenceType(),
            'stock_reference_id' => $part->getStockReferenceId(),
            'metadata' => $part->getMetadata(),
            'row_version' => $part->getRowVersion(),
            'created_at' => $part->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updated_at' => $part->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
