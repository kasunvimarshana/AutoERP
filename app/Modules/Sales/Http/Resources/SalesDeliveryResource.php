<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Sales\Http\Resources\Concerns\FormatsSalesResources;
use Modules\Sales\Services\SalesDocumentCapabilityService;
use Modules\Sales\Services\SalesFulfilmentBalanceService;
use Modules\Sales\Services\SalesRelatedDocumentService;

final class SalesDeliveryResource extends JsonResource
{
    use FormatsSalesResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'delivery_number' => $this->delivery_number,
            'delivery_date' => $this->delivery_date?->toDateString(),
            'status' => $this->enumValue($this->status),
            'status_label' => $this->statusLabel($this->status),
            'progress' => [
                'invoice' => app(SalesFulfilmentBalanceService::class)->salesDeliveryInvoiceStatus($this->whenLoaded('lines', fn () => $this->lines, collect())),
                'return' => app(SalesFulfilmentBalanceService::class)->salesDeliveryReturnStatus($this->whenLoaded('lines', fn () => $this->lines, collect())),
            ],
            'capabilities' => app(SalesDocumentCapabilityService::class)->forSalesDelivery($this->resource),
            'related_documents' => app(SalesRelatedDocumentService::class)->forSalesDelivery($this->resource),
            'sales_order' => $this->whenLoaded('salesOrder', fn () => $this->summary($this->salesOrder, ['sales_order_number', 'status'])),
            'customer' => $this->whenLoaded('customer', fn () => $this->summary($this->customer, ['customer_number', 'code', 'name', 'display_name'])),
            'warehouse' => $this->whenLoaded('warehouse', fn () => $this->summary($this->warehouse, ['code', 'name'])),
            'warehouse_location' => $this->whenLoaded('warehouseLocation', fn () => $this->summary($this->warehouseLocation, ['code', 'name'])),
            'notes' => $this->notes,
            'posted_at' => $this->posted_at?->toISOString(),
            'reversed_at' => $this->reversed_at?->toISOString(),
            'lines' => $this->whenLoaded(
                'lines',
                fn () => SalesDeliveryLineResource::collection($this->lines)->resolve($request),
                [],
            ),
            'adjustments' => $this->whenLoaded('adjustments', fn () => SalesHeaderAdjustmentResource::collection($this->adjustments)->resolve($request), []),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
