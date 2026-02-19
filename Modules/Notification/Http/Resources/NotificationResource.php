<?php

declare(strict_types=1);

namespace Modules\Notification\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'organization_id' => $this->organization_id,
            'user_id' => $this->user_id,
            'template_id' => $this->template_id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'channel' => $this->channel,
            'priority' => $this->priority->value,
            'priority_label' => $this->priority->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'subject' => $this->subject,
            'body' => $this->body,
            'data' => $this->data ?? [],
            'metadata' => $this->metadata ?? [],
            'is_read' => $this->isRead(),
            'is_sent' => $this->isSent(),
            'has_failed' => $this->hasFailed(),
            'sent_at' => $this->sent_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'read_at' => $this->read_at?->toISOString(),
            'failed_at' => $this->failed_at?->toISOString(),
            'scheduled_at' => $this->scheduled_at?->toISOString(),
            'error_message' => $this->error_message,
            'retry_count' => $this->retry_count,
            'max_retries' => $this->max_retries,
            'template' => new NotificationTemplateResource($this->whenLoaded('template')),
            'user' => $this->when(
                $this->relationLoaded('user'),
                fn () => [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ]
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}
