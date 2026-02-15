<?php

declare(strict_types=1);

namespace App\Http\Resources\Manufacturing;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'location_id' => $this->location_id,
            'product_id' => $this->product_id,
            'bom_id' => $this->bom_id,
            'work_order_number' => $this->work_order_number,
            'reference_number' => $this->reference_number,
            'status' => $this->status,
            'priority' => $this->priority,
            'planned_quantity' => $this->planned_quantity,
            'produced_quantity' => $this->produced_quantity,
            'scrap_quantity' => $this->scrap_quantity,
            'unit_id' => $this->unit_id,
            'planned_start_date' => $this->planned_start_date?->toDateString(),
            'planned_end_date' => $this->planned_end_date?->toDateString(),
            'actual_start_date' => $this->actual_start_date?->toIso8601String(),
            'actual_end_date' => $this->actual_end_date?->toIso8601String(),
            'estimated_cost' => $this->estimated_cost,
            'actual_cost' => $this->actual_cost,
            'material_cost' => $this->material_cost,
            'labor_cost' => $this->labor_cost,
            'overhead_cost' => $this->overhead_cost,
            'notes' => $this->notes,
            'production_instructions' => $this->production_instructions,
            'assigned_to' => $this->assigned_to,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            // Computed properties
            'estimated_cost_formatted' => $this->estimated_cost 
                ? number_format((float) $this->estimated_cost, 2) 
                : null,
            'actual_cost_formatted' => $this->actual_cost 
                ? number_format((float) $this->actual_cost, 2) 
                : null,
            'material_cost_formatted' => $this->material_cost 
                ? number_format((float) $this->material_cost, 2) 
                : null,
            'completion_percentage' => $this->completion_percentage,
            'remaining_quantity' => $this->remaining_quantity,
            'is_overdue' => $this->isOverdue(),
            'can_start' => $this->canStart(),
            'can_complete' => $this->canComplete(),
            'can_cancel' => $this->canCancel(),
            
            // Related data (when loaded)
            'product' => $this->whenLoaded('product', function () {
                return [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'sku' => $this->product->sku,
                ];
            }),
            'bom' => $this->whenLoaded('bom', function () {
                return [
                    'id' => $this->bom->id,
                    'bom_number' => $this->bom->bom_number,
                    'name' => $this->bom->name,
                    'version' => $this->bom->version,
                ];
            }),
            'branch' => $this->whenLoaded('branch', function () {
                return [
                    'id' => $this->branch->id,
                    'name' => $this->branch->name,
                ];
            }),
            'location' => $this->whenLoaded('location', function () {
                return [
                    'id' => $this->location->id,
                    'name' => $this->location->name,
                ];
            }),
            'unit' => $this->whenLoaded('unit', function () {
                return [
                    'id' => $this->unit->id,
                    'name' => $this->unit->name,
                    'symbol' => $this->unit->symbol,
                ];
            }),
            'assigned_to_user' => $this->whenLoaded('assignedTo', function () {
                return [
                    'id' => $this->assignedTo->id,
                    'name' => $this->assignedTo->name,
                    'email' => $this->assignedTo->email,
                ];
            }),
            'items' => $this->whenLoaded('items', function () {
                return WorkOrderItemResource::collection($this->items);
            }),
            'created_by_user' => $this->whenLoaded('createdBy', function () {
                return [
                    'id' => $this->createdBy->id,
                    'name' => $this->createdBy->name,
                ];
            }),
        ];
    }
}
