<?php

declare(strict_types=1);

namespace Modules\SystemUser\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'organization_unit_id' => $this->organization_unit_id,
            'user_id' => $this->user_id,
            'code' => $this->code,
            'registration_number' => $this->registration_number,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'metadata' => $this->metadata,
            'row_version' => $this->row_version,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
