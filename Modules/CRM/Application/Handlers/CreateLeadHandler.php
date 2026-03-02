<?php
declare(strict_types=1);
namespace Modules\CRM\Application\Handlers;
use Modules\CRM\Application\Commands\CreateLeadCommand;
use Modules\CRM\Domain\Contracts\LeadRepositoryInterface;
use Modules\CRM\Domain\Entities\Lead;
use Modules\CRM\Domain\Enums\LeadStatus;
class CreateLeadHandler {
    public function __construct(
        private readonly LeadRepositoryInterface $leads,
    ) {}
    public function handle(CreateLeadCommand $command): Lead {
        // Construct a transient domain entity (id=0 signals new record)
        $lead = new Lead(
            id: 0,
            tenantId: $command->tenantId,
            contactId: $command->contactId,
            title: $command->title,
            status: LeadStatus::NEW,
            source: $command->source,
            value: bcadd($command->value, '0', 4),
            expectedCloseDate: $command->expectedCloseDate,
            assignedTo: $command->assignedTo,
            notes: $command->notes,
        );
        return $this->leads->save($lead);
    }
}
