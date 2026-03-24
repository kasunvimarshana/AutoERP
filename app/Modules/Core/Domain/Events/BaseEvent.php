<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class BaseEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $tenantId;

    public ?int $orgUnitId;

    public function __construct(int $tenantId, ?int $orgUnitId = null)
    {
        $this->tenantId = $tenantId;
        $this->orgUnitId = $orgUnitId;
    }

    public function broadcastOn()
    {
        $channels = [new Channel('tenant.'.$this->tenantId)];
        if ($this->orgUnitId) {
            $channels[] = new Channel('org.'.$this->orgUnitId);
        }

        return $channels;
    }
}
