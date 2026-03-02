<?php

declare(strict_types=1);

namespace Modules\Notification\Interfaces\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Notification\Domain\Entities\Notification;

class NotificationResource extends JsonResource
{
    /** @var Notification */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'tenant_id' => $this->resource->tenantId,
            'user_id' => $this->resource->userId,
            'channel' => $this->resource->channel->value,
            'event_type' => $this->resource->eventType,
            'template_id' => $this->resource->templateId,
            'subject' => $this->resource->subject,
            'body' => $this->resource->body,
            'status' => $this->resource->status->value,
            'sent_at' => $this->resource->sentAt,
            'read_at' => $this->resource->readAt,
            'created_at' => $this->resource->createdAt,
            'updated_at' => $this->resource->updatedAt,
        ];
    }
}
