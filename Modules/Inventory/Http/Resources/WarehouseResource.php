<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
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
            'tenant_id' => $this->tenant_id,
            'organization_id' => $this->organization_id,
            'code' => $this->code,
            'name' => $this->name,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'address_line1' => $this->address_line1,
            'address_line2' => $this->address_line2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'phone' => $this->phone,
            'email' => $this->email,
            'manager_name' => $this->manager_name,
            'is_default' => $this->is_default,
            'notes' => $this->notes,
            'locations_count' => $this->when(
                $this->relationLoaded('locations'),
                fn () => $this->locations->count()
            ),
            'stock_items_count' => $this->when(
                $this->relationLoaded('stockItems'),
                fn () => $this->stockItems->count()
            ),
            'can_accept_stock' => $this->status->canAcceptStock(),
            'can_issue_stock' => $this->status->canIssueStock(),
            'is_active' => $this->status->isActive(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}
