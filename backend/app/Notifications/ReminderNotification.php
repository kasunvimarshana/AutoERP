<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly array $data
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'reminder',
            'title' => $this->data['title'] ?? 'Reminder',
            'message' => $this->data['message'] ?? '',
            'due_date' => $this->data['due_date'] ?? null,
            'action_url' => $this->data['action_url'] ?? null,
            'severity' => $this->data['severity'] ?? 'info',
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
