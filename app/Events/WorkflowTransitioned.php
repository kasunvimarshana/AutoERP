<?php

namespace App\Events;

use App\Contracts\Events\DomainEventInterface;
use App\Models\WorkflowInstance;
use App\Models\WorkflowTransition;
use DateTimeImmutable;

final class WorkflowTransitioned implements DomainEventInterface
{
    private DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly WorkflowInstance $instance,
        public readonly ?WorkflowTransition $transition,
        public readonly string $fromStateId,
        public readonly string $toStateId,
        public readonly ?string $transitionedBy = null,
        public readonly ?string $comment = null,
    ) {
        $this->occurredAt = new DateTimeImmutable;
    }

    public function getAggregateId(): string
    {
        return $this->instance->id;
    }

    public function getAggregateType(): string
    {
        return WorkflowInstance::class;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function toArray(): array
    {
        return [
            'instance_id' => $this->instance->id,
            'entity_type' => $this->instance->entity_type,
            'entity_id' => $this->instance->entity_id,
            'tenant_id' => $this->instance->tenant_id,
            'from_state_id' => $this->fromStateId,
            'to_state_id' => $this->toStateId,
            'transitioned_by' => $this->transitionedBy,
            'comment' => $this->comment,
            'occurred_at' => $this->occurredAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
