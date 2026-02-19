<?php

declare(strict_types=1);

namespace Modules\Document\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FolderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'organization_id' => $this->organization_id,
            'parent_folder_id' => $this->parent_folder_id,
            'name' => $this->name,
            'description' => $this->description,
            'path' => $this->path,
            'is_system' => $this->is_system,
            'metadata' => $this->metadata,
            'created_by' => $this->created_by,
            'parent' => $this->whenLoaded('parent', fn () => new FolderResource($this->parent)),
            'children' => $this->whenLoaded('children', fn () => FolderResource::collection($this->children)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
