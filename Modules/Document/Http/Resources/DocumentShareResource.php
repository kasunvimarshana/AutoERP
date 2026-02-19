<?php

declare(strict_types=1);

namespace Modules\Document\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentShareResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_id' => $this->document_id,
            'user_id' => $this->user_id,
            'permission_type' => $this->permission_type,
            'expires_at' => $this->expires_at,
            'is_active' => $this->isActive(),
            'is_expired' => $this->isExpired(),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'document' => $this->whenLoaded('document', fn () => new DocumentResource($this->document)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
