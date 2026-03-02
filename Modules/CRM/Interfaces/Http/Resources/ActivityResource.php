<?php

declare(strict_types=1);

namespace Modules\Crm\Interfaces\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Crm\Domain\Entities\Activity;

class ActivityResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var Activity $activity */
        $activity = $this->resource;

        return [
            'id' => $activity->id,
            'tenant_id' => $activity->tenantId,
            'contact_id' => $activity->contactId,
            'lead_id' => $activity->leadId,
            'type' => $activity->type->value,
            'subject' => $activity->subject,
            'description' => $activity->description,
            'scheduled_at' => $activity->scheduledAt,
            'completed_at' => $activity->completedAt,
            'created_at' => $activity->createdAt,
            'updated_at' => $activity->updatedAt,
        ];
    }
}
