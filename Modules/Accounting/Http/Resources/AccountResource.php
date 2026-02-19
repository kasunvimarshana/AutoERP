<?php

declare(strict_types=1);

namespace Modules\Accounting\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
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
            'parent_id' => $this->parent_id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'normal_balance' => $this->normal_balance,
            'is_system' => $this->is_system,
            'is_bank_account' => $this->is_bank_account,
            'is_reconcilable' => $this->is_reconcilable,
            'allow_manual_entries' => $this->allow_manual_entries,
            'metadata' => $this->metadata,
            'is_parent' => $this->isParent(),
            'is_leaf' => $this->isLeaf(),
            'hierarchy_path' => $this->hierarchyPath(),
            'parent' => new AccountResource($this->whenLoaded('parent')),
            'children' => AccountResource::collection($this->whenLoaded('children')),
            'children_count' => $this->when(
                $this->relationLoaded('children'),
                fn () => $this->children->count()
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}
