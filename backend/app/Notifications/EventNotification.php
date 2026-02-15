<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Core\Notifications\BaseNotification;
use App\Services\WebPush\WebPushService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

/**
 * Event Notification
 * 
 * Generic notification for domain events
 */
class EventNotification extends BaseNotification implements ShouldBroadcast
{
    public function __construct(
        string $title,
        string $message,
        string $type = 'info',
        ?string $actionUrl = null,
        array $metadata = []
    ) {
        $this->setTitle($title);
        $this->setMessage($message);
        $this->setType($type);
        
        if ($actionUrl) {
            $this->setAction($actionUrl);
        }
        
        if (!empty($metadata)) {
            $this->setMetadata($metadata);
        }
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];
        
        // Add broadcast channel if user has enabled push notifications
        if ($this->userHasEnabledPush($notifiable)) {
            $channels[] = 'broadcast';
            $channels[] = WebPushChannel::class;
        }
        
        return $channels;
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'action_url' => $this->actionUrl,
            'action_text' => $this->actionText,
            'metadata' => $this->metadata,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Get the Web Push representation of the notification
     */
    public function toWebPush(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->message,
            'icon' => '/images/logo.png',
            'badge' => '/images/badge.png',
            'tag' => 'notification-' . time(),
            'requireInteraction' => in_array($this->type, ['error', 'warning']),
            'data' => [
                'url' => $this->actionUrl ?? '/',
                'type' => $this->type,
                'metadata' => $this->metadata,
                'timestamp' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Get the broadcast channel
     */
    public function broadcastOn(): Channel
    {
        return new Channel('user.' . $this->notifiable->id);
    }

    /**
     * Check if user has enabled push notifications
     */
    private function userHasEnabledPush(object $notifiable): bool
    {
        // Check if user has any push subscriptions
        return $notifiable->pushSubscriptions()->exists();
    }
}
