<?php
namespace Modules\CRM\Domain\Events;
use Modules\Shared\Domain\Events\DomainEvent;
class LeadConverted extends DomainEvent
{
    public function __construct(
        public readonly string $leadId,
        public readonly string $opportunityId,
        public readonly string $tenantId = '',
        public readonly string $contactName = '',
        public readonly string $contactEmail = '',
        public readonly string $expectedRevenue = '0',
    ) {}
}
