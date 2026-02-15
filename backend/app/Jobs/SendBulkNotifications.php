<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class SendBulkNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        private readonly int $tenantId,
        private readonly array $userIds,
        private readonly string $notificationType,
        private readonly array $notificationData
    ) {}

    public function handle(): void
    {
        Log::info("Starting bulk notification send", [
            'tenant_id' => $this->tenantId,
            'user_count' => count($this->userIds),
            'notification_type' => $this->notificationType,
        ]);

        $sent = 0;
        $failed = 0;

        foreach ($this->userIds as $userId) {
            try {
                $user = User::where('tenant_id', $this->tenantId)
                    ->find($userId);

                if (!$user) {
                    $failed++;
                    continue;
                }

                $notification = $this->createNotification();
                $user->notify($notification);
                $sent++;
            } catch (\Exception $e) {
                $failed++;
                Log::error("Failed to send notification to user", [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info("Bulk notification send completed", [
            'sent' => $sent,
            'failed' => $failed,
        ]);
    }

    private function createNotification(): object
    {
        return match ($this->notificationType) {
            'announcement' => new \App\Notifications\AnnouncementNotification($this->notificationData),
            'reminder' => new \App\Notifications\ReminderNotification($this->notificationData),
            'alert' => new \App\Notifications\AlertNotification($this->notificationData),
            default => throw new \InvalidArgumentException("Unknown notification type: {$this->notificationType}"),
        };
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Bulk notification job failed", [
            'tenant_id' => $this->tenantId,
            'notification_type' => $this->notificationType,
            'error' => $exception->getMessage(),
        ]);
    }
}
