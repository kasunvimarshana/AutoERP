<?php

declare(strict_types=1);

namespace Modules\CRM\Infrastructure\Http\Resources;

use Modules\Core\Infrastructure\Http\Resources\BaseResource;

class ContactResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'contactable_type' => $this->contactable_type,
            'contactable_id'   => $this->contactable_id,
            'first_name'       => $this->first_name,
            'last_name'        => $this->last_name,
            'title'            => $this->title,
            'department'       => $this->department,
            'position'         => $this->position,
            'email'            => $this->email,
            'phone'            => $this->phone,
            'mobile'           => $this->mobile,
            'is_primary'       => $this->is_primary,
            'notes'            => $this->notes,
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }
}
