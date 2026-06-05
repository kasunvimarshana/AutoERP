<?php

declare(strict_types=1);

namespace Modules\VehicleService\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class JobCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'organization_unit_id' => $this->resource->organization_unit_id ?? null,
            'job_card_number' => $this->resource->job_card_number,
            'reference' => $this->resource->reference,
            'service_type_id' => $this->resource->service_type_id ?? null,
            'service_type_name' => $this->resource->service_type_name ?? null,
            'vehicle_id' => $this->resource->vehicle_id ?? null,
            'registration_number' => $this->resource->registration_number ?? null,
            'vehicle_make' => $this->resource->vehicle_make ?? null,
            'vehicle_model' => $this->resource->vehicle_model ?? null,
            'linked_customer_id' => $this->resource->linked_customer_id ?? null,
            'customer_name' => $this->resource->customer_name ?? null,
            'warehouse_id' => $this->resource->warehouse_id ?? null,
            'warehouse_name' => $this->resource->warehouse_name ?? null,
            'priority' => $this->resource->priority,
            'status' => $this->resource->status,
            'inventory_status' => $this->resource->inventory_status,
            'invoice_status' => $this->resource->invoice_status,
            'payment_status' => $this->resource->payment_status,
            'finance_status' => $this->resource->finance_status,
            'reported_issue' => $this->resource->reported_issue ?? null,
            'resolution_notes' => $this->resource->resolution_notes ?? null,
            'technician_notes' => $this->resource->technician_notes ?? null,
            'start_datetime' => $this->resource->start_datetime ?? null,
            'completed_datetime' => $this->resource->completed_datetime ?? null,
            'promised_delivery_date_time' => $this->resource->promised_delivery_date_time ?? null,
            'estimated_hours' => $this->nullableMoney($this->resource->estimated_hours ?? null),
            'actual_hours' => $this->nullableMoney($this->resource->actual_hours ?? null),
            'start_odometer' => $this->resource->start_odometer ?? null,
            'end_odometer' => $this->resource->end_odometer ?? null,
            'next_service_odometer' => $this->resource->next_service_odometer ?? null,
            'next_service_date' => $this->resource->next_service_date ?? null,
            'subtotal' => $this->money($this->resource->subtotal),
            'labor_item_subtotal' => $this->money($this->resource->labor_item_subtotal),
            'non_inventory_item_subtotal' => $this->money($this->resource->non_inventory_item_subtotal),
            'line_discount_total' => $this->money((float) $this->resource->line_discount_total + (float) $this->resource->labor_item_discount_total + (float) $this->resource->non_inventory_item_discount_total),
            'header_discount_total' => $this->money($this->resource->header_discount_amount),
            'discount_total' => $this->money($this->resource->discount_total),
            'line_tax_total' => $this->money((float) $this->resource->line_tax_total + (float) $this->resource->labor_item_tax_total + (float) $this->resource->non_inventory_item_tax_total),
            'header_tax_total' => $this->money($this->resource->header_tax_amount),
            'tax_total' => $this->money($this->resource->tax_total),
            'charge_total' => $this->money($this->resource->debit_note_total),
            'adjustment_total' => $this->money((float) $this->resource->debit_note_total - (float) $this->resource->credit_note_total),
            'credit_adjustment_total' => $this->money($this->resource->credit_note_total),
            'grand_total' => $this->money($this->resource->grand_total),
            'paid_amount' => $this->money($this->resource->paid_amount),
            'balance' => $this->money($this->resource->balance),
            'notes' => $this->resource->notes ?? null,
            'parts' => $this->when(isset($this->resource->parts), $this->resource->parts ?? []),
            'labor_items' => $this->when(isset($this->resource->labor_items), $this->resource->labor_items ?? []),
            'non_inventory_items' => $this->when(isset($this->resource->non_inventory_items), $this->resource->non_inventory_items ?? []),
            'invoice_links' => $this->when(isset($this->resource->invoice_links), $this->resource->invoice_links ?? []),
            'payments' => $this->when(isset($this->resource->payments), $this->resource->payments ?? []),
            'created_at' => $this->resource->created_at,
        ];
    }

    private function money(mixed $value): string
    {
        return number_format((float) $value, 4, '.', '');
    }

    private function nullableMoney(mixed $value): ?string
    {
        return $value === null ? null : $this->money($value);
    }
}
