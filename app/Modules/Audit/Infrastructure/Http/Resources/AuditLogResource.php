<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->resource->id,
            'tenant_id'      => $this->resource->tenantId,
            'user_id'        => $this->resource->userId,
            'event'          => $this->resource->event,
            'auditable_type' => $this->resource->auditableType,
            'auditable_id'   => $this->resource->auditableId,
            'old_values'     => $this->resource->oldValues,
            'new_values'     => $this->resource->newValues,
            'diff'           => $this->resource->getDiff(),
            'url'            => $this->resource->url,
            'ip_address'     => $this->resource->ipAddress,
            'user_agent'     => $this->resource->userAgent,
            'tags'           => $this->resource->tags,
            'created_at'     => $this->resource->createdAt,
        ];
    }
}
