<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationUnitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'type_id' => $this->type_id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'code' => $this->code,
            'image_path' => $this->image_path,
            'path' => $this->path,
            'depth' => $this->depth,
            'is_active' => $this->is_active,
            'description' => $this->description,
            '_lft' => $this->_lft,
            '_rgt' => $this->_rgt,
            'metadata' => $this->metadata,
            'row_version' => $this->row_version,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
