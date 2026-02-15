<?php

declare(strict_types=1);

namespace App\Core\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

/**
 * Base Notification Class
 * 
 * All application notifications should extend this class
 */
abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $title;
    protected string $message;
    protected string $type = 'info'; // info, success, warning, error
    protected ?string $actionUrl = null;
    protected ?string $actionText = null;
    protected array $metadata = [];

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
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
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    /**
     * Set notification title
     *
     * @param string $title
     * @return $this
     */
    protected function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Set notification message
     *
     * @param string $message
     * @return $this
     */
    protected function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Set notification type
     *
     * @param string $type
     * @return $this
     */
    protected function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Set action URL and text
     *
     * @param string $url
     * @param string $text
     * @return $this
     */
    protected function setAction(string $url, string $text = 'View'): self
    {
        $this->actionUrl = $url;
        $this->actionText = $text;
        return $this;
    }

    /**
     * Set metadata
     *
     * @param array $metadata
     * @return $this
     */
    protected function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }
}
