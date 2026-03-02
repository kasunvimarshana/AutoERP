<?php

declare(strict_types=1);

namespace Modules\Crm\Interfaces\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Crm\Domain\Entities\Lead;

class LeadResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var Lead $lead */
        $lead = $this->resource;

        return [
            'id' => $lead->id,
            'tenant_id' => $lead->tenantId,
            'contact_id' => $lead->contactId,
            'title' => $lead->title,
            'description' => $lead->description,
            'status' => $lead->status->value,
            'estimated_value' => $lead->estimatedValue,
            'currency' => $lead->currency,
            'expected_close_date' => $lead->expectedCloseDate,
            'notes' => $lead->notes,
            'created_at' => $lead->createdAt,
            'updated_at' => $lead->updatedAt,
        ];
    }
}
