<?php

declare(strict_types=1);

namespace Modules\CRM\Application\Handlers;

use Modules\CRM\Application\Commands\ConvertLeadCommand;
use Modules\CRM\Domain\Contracts\LeadRepositoryInterface;
use Modules\CRM\Domain\Contracts\OpportunityRepositoryInterface;
use Modules\CRM\Domain\Entities\Opportunity;

class ConvertLeadHandler
{
    public function __construct(
        private readonly LeadRepositoryInterface        $leads,
        private readonly OpportunityRepositoryInterface $opportunities,
    ) {}

    public function handle(ConvertLeadCommand $command): Opportunity
    {
        $lead = $this->leads->findById($command->leadId, $command->tenantId);

        if ($lead === null) {
            throw new \DomainException("Lead #{$command->leadId} not found.");
        }

        $opportunity = new Opportunity(
            id: 0,
            tenantId: $lead->getTenantId(),
            leadId: $lead->getId(),
            contactId: $lead->getContactId(),
            title: $lead->getTitle(),
            stage: 'prospecting',
            value: $lead->getValue(),
            probability: '0.1000',
            expectedCloseDate: $lead->getExpectedCloseDate(),
            assignedTo: $lead->getAssignedTo(),
            notes: $lead->getNotes(),
        );

        return $this->opportunities->save($opportunity);
    }
}
