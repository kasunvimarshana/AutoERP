<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Sales\Http\Resources\Concerns\FormatsSalesResources;
use Modules\Sales\Services\SalesDocumentCapabilityService;
use Modules\Sales\Services\SalesProgressService;
use Modules\Sales\Services\SalesRelatedDocumentService;

final class SalesOrderResource extends JsonResource
{
    use FormatsSalesResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'sales_order_number' => $this->sales_order_number,
            'sales_order_date' => $this->sales_order_date?->toDateString(),
            'expected_delivery_date' => $this->expected_delivery_date?->toDateString(),
            'status' => $this->enumValue($this->status),
            'status_label' => $this->statusLabel($this->status),
            'workflow_status' => $this->enumValue($this->status),
            'progress' => app(SalesProgressService::class)->forSalesOrder($this->resource),
            'capabilities' => app(SalesDocumentCapabilityService::class)->forSalesOrder($this->resource),
            'related_documents' => app(SalesRelatedDocumentService::class)->forSalesOrder($this->resource),
            'customer_id' => $this->customer_id,
            'customer' => $this->whenLoaded('customer', fn () => $this->summary($this->customer, ['customer_number', 'code', 'name', 'display_name', 'payment_term_id', 'default_currency_id'])),
            'quotation' => $this->whenLoaded('quotation', fn () => $this->summary($this->quotation, ['quotation_number', 'quotation_date', 'status'])),
            'warehouse_id' => $this->warehouse_id,
            'warehouse' => $this->whenLoaded('warehouse', fn () => $this->summary($this->warehouse, ['code', 'name'])),
            'warehouse_location_id' => $this->warehouse_location_id,
            'warehouse_location' => $this->whenLoaded('warehouseLocation', fn () => $this->summary($this->warehouseLocation, ['code', 'name'])),
            'currency_id' => $this->currency_id,
            'currency' => $this->whenLoaded('currency', fn () => $this->summary($this->currency, ['code', 'name', 'symbol'])),
            'exchange_rate' => (string) $this->exchange_rate,
            'subtotal' => (string) $this->subtotal,
            'line_discount_total' => (string) $this->line_discount_total,
            'line_tax_total' => (string) $this->line_tax_total,
            'line_charge_total' => (string) $this->line_charge_total,
            'header_increase_total' => (string) $this->header_increase_total,
            'header_decrease_total' => (string) $this->header_decrease_total,
            'grand_total' => (string) $this->grand_total,
            'allocated_total' => (string) $this->allocated_total,
            'delivered_total' => (string) $this->delivered_total,
            'invoiced_total' => (string) $this->invoiced_total,
            'returned_total' => (string) $this->returned_total,
            'notes' => $this->notes,
            'approved_at' => $this->approved_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'closed_at' => $this->closed_at?->toISOString(),
            'lines' => $this->whenLoaded(
                'lines',
                fn () => SalesOrderLineResource::collection($this->lines)->resolve($request),
                [],
            ),
            'adjustments' => $this->whenLoaded('adjustments', fn () => SalesHeaderAdjustmentResource::collection($this->adjustments)->resolve($request), []),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
