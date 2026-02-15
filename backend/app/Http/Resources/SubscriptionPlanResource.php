<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPlanResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => (float) $this->price,
            'billing_cycle' => $this->billing_cycle,
            'trial_days' => $this->trial_days,
            'max_users' => $this->max_users,
            'max_organizations' => $this->max_organizations,
            'max_branches' => $this->max_branches,
            'max_products' => $this->max_products,
            'max_invoices_per_month' => $this->max_invoices_per_month,
            'features' => $this->features,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            // Computed properties
            'has_trial' => $this->trial_days > 0,
            'has_user_limit' => $this->max_users !== null,
            'has_organization_limit' => $this->max_organizations !== null,
            'has_branch_limit' => $this->max_branches !== null,
            'has_product_limit' => $this->max_products !== null,
            'price_formatted' => number_format((float) $this->price, 2),
            
            // Related data (when loaded)
            'tenants' => $this->whenLoaded('tenants', function () {
                return TenantResource::collection($this->tenants);
            }),
            'tenants_count' => $this->when(
                isset($this->tenants_count),
                $this->tenants_count
            ),
        ];
    }
}
