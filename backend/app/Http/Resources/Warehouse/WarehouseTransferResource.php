<?php

declare(strict_types=1);

namespace App\Http\Resources\Warehouse;

use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseTransferResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'transfer_number' => $this->transfer_number,
            'reference_number' => $this->reference_number,
            'status' => $this->status,
            'priority' => $this->priority,
            'transfer_date' => $this->transfer_date?->format('Y-m-d'),
            'expected_delivery_date' => $this->expected_delivery_date?->format('Y-m-d'),
            'actual_delivery_date' => $this->actual_delivery_date?->format('Y-m-d'),
            'shipping_cost' => $this->shipping_cost,
            'handling_cost' => $this->handling_cost,
            'total_cost' => $this->total_cost,
            'tracking_number' => $this->tracking_number,
            'carrier' => $this->carrier,
            'notes' => $this->notes,
            'completion_percentage' => $this->completion_percentage,
            
            'source_branch' => $this->whenLoaded('sourceBranch', fn() => [
                'id' => $this->sourceBranch->id,
                'name' => $this->sourceBranch->name,
            ]),
            'destination_branch' => $this->whenLoaded('destinationBranch', fn() => [
                'id' => $this->destinationBranch->id,
                'name' => $this->destinationBranch->name,
            ]),
            'source_location' => $this->whenLoaded('sourceLocation', fn() => [
                'id' => $this->sourceLocation->id,
                'name' => $this->sourceLocation->name,
            ]),
            'destination_location' => $this->whenLoaded('destinationLocation', fn() => [
                'id' => $this->destinationLocation->id,
                'name' => $this->destinationLocation->name,
            ]),
            
            'items' => WarehouseTransferItemResource::collection($this->whenLoaded('items')),
            
            'approved_by' => $this->whenLoaded('approvedBy', fn() => [
                'id' => $this->approvedBy->id,
                'name' => $this->approvedBy->name,
            ]),
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
