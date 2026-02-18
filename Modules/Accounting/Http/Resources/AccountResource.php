<?php

declare(strict_types=1);

namespace Modules\Accounting\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'account_number' => $this->account_number,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'sub_type' => $this->sub_type,
            'parent_id' => $this->parent_id,
            'currency' => $this->currency,
            'is_active' => $this->is_active,
            'is_system' => $this->is_system,
            'balance' => $this->balance,
            'allow_manual_entries' => $this->allow_manual_entries,
            'tags' => $this->tags,
            'parent' => new AccountResource($this->whenLoaded('parent')),
            'children' => AccountResource::collection($this->whenLoaded('children')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
