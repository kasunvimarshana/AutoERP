<?php

declare(strict_types=1);

namespace Modules\Document\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'organization_id' => $this->organization_id,
            'folder_id' => $this->folder_id,
            'owner_id' => $this->owner_id,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'human_readable_size' => $this->human_readable_size,
            'original_name' => $this->original_name,
            'extension' => $this->extension,
            'version' => $this->version,
            'is_latest_version' => $this->is_latest_version,
            'parent_document_id' => $this->parent_document_id,
            'access_level' => $this->access_level,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'download_count' => $this->download_count,
            'view_count' => $this->view_count,
            'folder' => $this->whenLoaded('folder', fn () => new FolderResource($this->folder)),
            'owner' => $this->whenLoaded('owner', fn () => [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
                'email' => $this->owner->email,
            ]),
            'tags' => $this->whenLoaded('tags', fn () => DocumentTagResource::collection($this->tags)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
