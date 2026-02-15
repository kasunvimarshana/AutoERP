<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Core\Events\BaseEvent;

/**
 * Lead Created Event
 * 
 * Dispatched when a new lead is created in the CRM
 */
class LeadCreated extends BaseEvent
{
    public function __construct(
        public readonly int $leadId,
        public readonly string $leadName,
        public readonly ?string $leadEmail = null,
        public readonly ?string $leadPhone = null,
        public readonly ?string $source = null,
        public readonly ?int $assignedToUserId = null,
        int $tenantId,
        ?int $userId = null,
        ?array $metadata = null
    ) {
        parent::__construct($tenantId, $userId, $metadata);
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'lead_id' => $this->leadId,
            'lead_name' => $this->leadName,
            'lead_email' => $this->leadEmail,
            'lead_phone' => $this->leadPhone,
            'source' => $this->source,
            'assigned_to_user_id' => $this->assignedToUserId,
        ]);
    }
}
