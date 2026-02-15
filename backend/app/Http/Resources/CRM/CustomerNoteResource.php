<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerNoteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'customer_id' => $this->customer_id,
            'type' => $this->type,
            'subject' => $this->subject,
            'content' => $this->content,
            'interaction_date' => $this->interaction_date?->toIso8601String(),
            'duration_minutes' => $this->duration_minutes,
            'formatted_duration' => $this->getFormattedDuration(),
            'outcome' => $this->outcome,
            'is_private' => $this->is_private,
            'is_important' => $this->is_important,
            'is_pinned' => $this->is_pinned,
            'attachments' => $this->attachments,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                ];
            }),
        ];
    }
}
