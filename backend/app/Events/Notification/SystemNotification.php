<?php

declare(strict_types=1);

namespace App\Events\Notification;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class SystemNotification implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $type;
    public string $title;
    public string $message;
    public array $data;
    public int $userId;
    public int $tenantId;

    public function __construct(
        string $type,
        string $title,
        string $message,
        array $data,
        int $userId,
        int $tenantId
    ) {
        $this->type = $type;
        $this->title = $title;
        $this->message = $message;
        $this->data = $data;
        $this->userId = $userId;
        $this->tenantId = $tenantId;
    }

    public function broadcastOn(): array
    {
        return [
            "tenant.{$this->tenantId}.user.{$this->userId}",
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'data' => $this->data,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'notification';
    }
}
