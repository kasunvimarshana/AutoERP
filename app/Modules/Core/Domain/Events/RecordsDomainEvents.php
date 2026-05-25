<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Events;

trait RecordsDomainEvents
{
    /** @var DomainEventInterface[] */
    private array $recordedEvents = [];

    protected function recordEvent(DomainEventInterface $event): void
    {
        $this->recordedEvents[] = $event;
    }

    /**
     * @return DomainEventInterface[]
     */
    public function releaseEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }
}
