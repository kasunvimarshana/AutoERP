<?php
namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function __construct(
        private EmailNotificationService $emailService,
        private SmsNotificationService   $smsService,
    ) {}

    public function sendOrderConfirmation(string $sagaId, string $orderId, string $tenantId, array $recipient, array $orderData): void
    {
        $channels = $this->resolveChannels($recipient);
        foreach ($channels as $channel) {
            $notification = Notification::create([
                'saga_id'         => $sagaId,
                'order_id'        => $orderId,
                'tenant_id'       => $tenantId,
                'recipient_id'    => $recipient['id'] ?? null,
                'recipient_email' => $recipient['email'] ?? null,
                'recipient_phone' => $recipient['phone'] ?? null,
                'type'            => 'order_confirmed',
                'channel'         => $channel,
                'status'          => 'pending',
                'subject'         => "Order #{$orderId} Confirmed",
                'content'         => $this->buildContent('order_confirmed', $orderData),
                'metadata'        => ['order_data' => $orderData],
            ]);
            $this->sendNotification($notification);
        }
    }

    public function sendOrderCancellation(string $sagaId, string $orderId, string $tenantId, array $recipient, array $orderData): void
    {
        $channels = $this->resolveChannels($recipient);
        foreach ($channels as $channel) {
            $notification = Notification::create([
                'saga_id'         => $sagaId,
                'order_id'        => $orderId,
                'tenant_id'       => $tenantId,
                'recipient_id'    => $recipient['id'] ?? null,
                'recipient_email' => $recipient['email'] ?? null,
                'recipient_phone' => $recipient['phone'] ?? null,
                'type'            => 'order_cancelled',
                'channel'         => $channel,
                'status'          => 'pending',
                'subject'         => "Order #{$orderId} Cancelled",
                'content'         => $this->buildContent('order_cancelled', $orderData),
                'metadata'        => ['order_data' => $orderData],
            ]);
            $this->sendNotification($notification);
        }
    }

    public function sendNotification(Notification $notification): bool
    {
        $notification->increment('retry_count');
        $sent = match ($notification->channel) {
            'email' => $this->emailService->sendEmail(
                $notification->recipient_email,
                $notification->subject,
                $notification->content,
                $notification->type,
            ),
            'sms'   => $this->smsService->sendSms(
                $notification->recipient_phone,
                $notification->content,
            ),
            default => false,
        };

        $notification->update($sent
            ? ['status' => 'sent',   'sent_at'   => now()]
            : ['status' => 'failed', 'failed_at' => now()]
        );

        $this->cacheToRedis($notification);
        return $sent;
    }

    private function resolveChannels(array $recipient): array
    {
        $channels = [];
        if (!empty($recipient['email'])) $channels[] = 'email';
        if (!empty($recipient['phone'])) $channels[] = 'sms';
        return $channels ?: ['email'];
    }

    private function buildContent(string $type, array $data): string
    {
        return match ($type) {
            'order_confirmed' => "Your order #{$data['order_id']} has been confirmed. Total: {$data['total']}.",
            'order_cancelled' => "Your order #{$data['order_id']} has been cancelled. Reason: {$data['reason']}.",
            default           => "Notification for order #{$data['order_id']}.",
        };
    }

    private function cacheToRedis(Notification $notification): void
    {
        try {
            Redis::setex(
                "notification:{$notification->id}",
                3600,
                json_encode($notification->toArray())
            );
        } catch (\Throwable $e) {
            Log::warning('Redis cache failed', ['error' => $e->getMessage()]);
        }
    }
}
