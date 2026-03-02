<?php

declare(strict_types=1);

namespace Modules\CRM\Application\Handlers;

use Modules\CRM\Application\Commands\UpdateLeadCommand;
use Modules\CRM\Domain\Contracts\LeadRepositoryInterface;
use Modules\CRM\Domain\Entities\Lead;
use Modules\CRM\Domain\Enums\LeadStatus;

class UpdateLeadHandler
{
    public function __construct(
        private readonly LeadRepositoryInterface $leads,
    ) {}

    public function handle(UpdateLeadCommand $command): Lead
    {
        $lead = $this->leads->findById($command->id, $command->tenantId);

        if ($lead === null) {
            throw new \DomainException("Lead #{$command->id} not found.");
        }

        $updated = new Lead(
            id: $lead->getId(),
            tenantId: $lead->getTenantId(),
            contactId: $lead->getContactId(),
            title: $command->title,
            status: LeadStatus::from($command->status),
            source: $command->source,
            value: bcadd($command->value, '0', 4),
            expectedCloseDate: $command->expectedCloseDate,
            assignedTo: $command->assignedTo,
            notes: $command->notes,
        );

        return $this->leads->save($updated);
    }
}
