<?php
namespace Modules\Shared\Domain\Events;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
abstract class DomainEvent
{
    use Dispatchable, SerializesModels;
    public readonly string $eventId;
    public readonly \DateTimeImmutable $occurredAt;
    public function __construct()
    {
        $this->eventId = (string) \Illuminate\Support\Str::uuid();
        $this->occurredAt = new \DateTimeImmutable();
    }
}
