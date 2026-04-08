<?php

declare(strict_types=1);

namespace Modules\Financial\Infrastructure\Http\Resources;

use Modules\Core\Infrastructure\Http\Resources\BaseResource;

class AccountResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'parent_id'      => $this->parent_id,
            'code'           => $this->code,
            'name'           => $this->name,
            'type'           => $this->type,
            'sub_type'       => $this->sub_type,
            'normal_balance' => $this->normal_balance,
            'currency_code'  => $this->currency_code,
            'description'    => $this->description,
            'is_active'      => $this->is_active,
            'is_system'      => $this->is_system,
            'metadata'       => $this->metadata,
            'created_at'     => $this->created_at?->toIso8601String(),
            'updated_at'     => $this->updated_at?->toIso8601String(),
        ];
    }
}
