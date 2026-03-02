<?php

declare(strict_types=1);

namespace Modules\Notification\Interfaces\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Notification\Domain\Entities\NotificationTemplate;

class NotificationTemplateResource extends JsonResource
{
    /** @var NotificationTemplate */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'tenant_id' => $this->resource->tenantId,
            'channel' => $this->resource->channel->value,
            'event_type' => $this->resource->eventType,
            'name' => $this->resource->name,
            'subject' => $this->resource->subject,
            'body' => $this->resource->body,
            'is_active' => $this->resource->isActive,
            'created_at' => $this->resource->createdAt,
            'updated_at' => $this->resource->updatedAt,
        ];
    }
}
