<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TrainingResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'               => $this->getId(),
            'tenant_id'        => $this->getTenantId(),
            'title'            => $this->getTitle(),
            'description'      => $this->getDescription(),
            'trainer'          => $this->getTrainer(),
            'location'         => $this->getLocation(),
            'start_date'       => $this->getStartDate(),
            'end_date'         => $this->getEndDate(),
            'max_participants' => $this->getMaxParticipants(),
            'status'           => $this->getStatus(),
            'metadata'         => $this->getMetadata()->toArray(),
            'is_active'        => $this->isActive(),
            'created_at'       => $this->getCreatedAt()?->format('c'),
            'updated_at'       => $this->getUpdatedAt()?->format('c'),
        ];
    }
}
