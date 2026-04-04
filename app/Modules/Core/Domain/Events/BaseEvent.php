<?php

namespace Modules\Core\Domain\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class BaseEvent implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $tenantId,
        public readonly ?int $orgUnitId = null,
    ) {}

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('tenant.'.$this->tenantId)];

        if ($this->orgUnitId !== null) {
            $channels[] = new PrivateChannel('org.'.$this->orgUnitId);
        }

        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'tenantId'  => $this->tenantId,
            'orgUnitId' => $this->orgUnitId,
        ];
    }
}
