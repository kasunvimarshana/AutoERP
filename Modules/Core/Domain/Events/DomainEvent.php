<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Base domain event.
 *
 * All domain events in every module must extend this class.
 */
abstract class DomainEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public readonly \DateTimeImmutable $occurredAt;

    public function __construct()
    {
        $this->occurredAt = new \DateTimeImmutable();
    }
}
