<?php
namespace Modules\CRM\Domain\Events;
use Modules\Shared\Domain\Events\DomainEvent;
class OpportunityStageChanged extends DomainEvent
{
    public function __construct(
        public readonly string $opportunityId,
        public readonly string $fromStage,
        public readonly string $toStage,
    ) {}
}
