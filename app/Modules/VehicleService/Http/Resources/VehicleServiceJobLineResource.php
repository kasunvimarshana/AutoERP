<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class VehicleServiceJobLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'parent_line_id' => $this->parent_line_id,
            'parent_line' => $this->whenLoaded('parent', fn () => $this->lineSummary($this->parent)),
            'line_number' => (int) $this->line_number,
            'line_source_type' => $this->enum($this->line_source_type),
            'item_id' => $this->item_id,
            'item' => $this->whenLoaded('item', fn () => $this->named($this->item)),
            'item_variant_id' => $this->item_variant_id,
            'item_variant' => $this->whenLoaded('variant', fn () => $this->named($this->variant)),
            'uom_id' => $this->uom_id,
            'uom' => $this->whenLoaded('uom', fn () => $this->named($this->uom)),
            'description' => $this->description,
            'quantity' => (string) $this->quantity,
            'unit_cost' => (string) $this->unit_cost,
            'uses_job_supervisor' => (bool) $this->uses_job_supervisor,
            'unit_price' => (string) $this->unit_price,
            'discount_calculation_type' => $this->discount_calculation_type,
            'discount_rate' => (string) $this->discount_rate,
            'discount_amount' => (string) $this->discount_amount,
            'tax_calculation_type' => $this->tax_calculation_type,
            'tax_rate' => (string) $this->tax_rate,
            'tax_amount' => (string) $this->tax_amount,
            'charge_calculation_type' => $this->charge_calculation_type,
            'charge_rate' => (string) $this->charge_rate,
            'charge_amount' => (string) $this->charge_amount,
            'line_total' => (string) $this->line_total,
            'is_inventory_tracked' => (bool) $this->is_inventory_tracked,
            'is_customer_supplied' => (bool) $this->is_customer_supplied,
            'is_external' => (bool) $this->is_external,
            'is_billable' => (bool) $this->is_billable,
            'is_employee_assignable' => (bool) $this->is_employee_assignable,
            'commission_default' => $this->when(
                $this->offsetExists('commission_default'),
                fn () => $this->commission_default,
                null,
            ),
            'inventory_movement_id' => $this->inventory_movement_id,
            'available_stock_quantity' => $this->when(
                $this->offsetExists('available_stock_quantity'),
                fn () => $this->available_stock_quantity === null ? null : (string) $this->available_stock_quantity,
            ),
            'stock_on_hand' => $this->when($this->offsetExists('stock_on_hand'), fn () => (string) $this->stock_on_hand),
            'stock_available' => $this->when($this->offsetExists('stock_available'), fn () => (string) $this->stock_available),
            'issue_eligible' => $this->when($this->offsetExists('issue_eligible'), fn () => (bool) $this->issue_eligible),
            'inventory_warning' => $this->when($this->offsetExists('inventory_warning'), fn () => $this->inventory_warning),
            'invoiced_quantity' => $this->when($this->offsetExists('invoiced_quantity'), fn () => (string) $this->invoiced_quantity),
            'remaining_billable_quantity' => $this->when($this->offsetExists('remaining_billable_quantity'), fn () => (string) $this->remaining_billable_quantity),
            'invoice_state' => $this->when($this->offsetExists('invoice_state'), fn () => $this->invoice_state),
            'status' => $this->enum($this->status),
            'children' => $this->whenLoaded('children', fn () => self::collection($this->children)->resolve($request), []),
            'employee_assignments' => $this->whenLoaded('employeeAssignments', fn () => VehicleServiceEmployeeAssignmentResource::collection($this->employeeAssignments)->resolve($request), []),
        ];
    }

    private function enum(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }

    private function named(mixed $model): ?array
    {
        if ($model === null) {
            return null;
        }

        return [
            'id' => (int) $model->getKey(),
            'code' => $model->code ?? $model->sku ?? $model->symbol ?? null,
            'name' => $model->name ?? $model->description ?? null,
        ];
    }

    private function lineSummary(mixed $line): ?array
    {
        if ($line === null) {
            return null;
        }

        return [
            'id' => (int) $line->getKey(),
            'line_number' => (int) $line->line_number,
            'description' => $line->description,
        ];
    }
}
